<?php

namespace App\Services;

use App\Models\UtilityProfile;
use Carbon\Carbon;

class UtilityReceiptLocalParserService
{
    /**
     * Disambiguate common OCR misread characters in numeric contexts
     */
    public function disambiguateOcrDigits(string $str): string
    {
        $map = [
            'O' => '0', 'o' => '0', 'D' => '0', 'Q' => '0',
            'I' => '1', 'l' => '1', '|' => '1', '!' => '1', 'i' => '1',
            'Z' => '2', 'z' => '2',
            'S' => '5', 's' => '5', '$' => '5',
            'b' => '6',
            'B' => '8',
            'g' => '9', 'q' => '9',
        ];

        return strtr($str, $map);
    }

    /**
     * Detect receipt structure: PLN Prabayar (token), PLN Pascabayar (monthly bill), Telkom, or Internet
     */
    public function detectUtilityStructure(string $text): array
    {
        $upper = strtoupper($text);

        // Strong signals for Prabayar (Token)
        $praSignals = 0;
        if (preg_match('/(PRABAYAR|PREPAID|BELI\s*STROOM|STROOM\s*[\/\-]?\s*TOKEN|TOKEN\s*LISTRIK|RUPIAH\s*TOKEN|STROM|NOMOR\s*METER|RP\s*STROOM|TAGIHAN\s*PLN\s*PREPAID|STRUK\s*PEMBELIAN\s*LISTRIK\s*PRABAYAR)/i', $upper)) {
            $praSignals += 4;
        }
        if (preg_match('/(JML\s*KWH|JUMLAH\s*KWH|KWH\s*:)/i', $upper)) {
            $praSignals += 2;
        }
        if (preg_match('/(?:STROOM|TOKEN)\D*([0-9A-Z]{4}[-\s][0-9A-Z]{4}[-\s][0-9A-Z]{4}[-\s][0-9A-Z]{4}[-\s][0-9A-Z]{4})/i', $upper)) {
            $praSignals += 5;
        }

        // Strong signals for Pascabayar (Tagihan Bulanan Listrik)
        $pascaSignals = 0;
        if (preg_match('/(PASCABAYAR|POSTPAID|TAGIHAN\s*LISTRIK|LEMBAR\s*TAGIHAN|TAGIHAN\s*PLN|REK\s*LISTRIK|TAGIHAN\s*PLN\s*POSTPAID|STRUK\s*PEMBAYARAN\s*TAGIHAN\s*LISTRIK|RP\s*TAG\s*PLN)/i', $upper)) {
            $pascaSignals += 4;
        }
        if (preg_match('/(STAND\s*METER|STAND\s*MTR|STAND\s*LALU|STAND\s*KINI)/i', $upper)) {
            $pascaSignals += 3;
        }
        if (preg_match('/(BL\s*[\/\-]\s*TH|BULAN\s*[\/\-]\s*TAHUN|REK\s*[\/\-]\s*BLN|TAG\s*[\/\-]\s*BLN)/i', $upper)) {
            $pascaSignals += 3;
        }

        // Check Telkom / Speedy
        $isTelkom = (bool) preg_match('/(TELKOM|SPEEDY|INDIHOME|TELEPON|0354[- ]?000479487|0354[- ]?004740750)/i', $upper);

        // Check Internet / Bank Transfer
        $isInternet = (bool) preg_match('/(MENAKSOPAL|LINK\s*NUSANTAR|TRANSFER\s*ATM|TRANSFER\s*KE\s*:\s*BCA|3243258444)/i', $upper);

        $isPrabayar = ($praSignals > $pascaSignals) && ! $isTelkom && ! $isInternet;
        $isPascabayar = ($pascaSignals >= $praSignals && $pascaSignals > 0) && ! $isTelkom && ! $isInternet;

        $utilityType = 'lainnya';
        if ($isInternet) {
            $utilityType = 'internet';
        } elseif ($isTelkom) {
            $utilityType = 'telkom_speedy';
        } elseif ($isPascabayar) {
            $utilityType = 'listrik_pasca';
        } elseif ($isPrabayar) {
            $utilityType = 'listrik_pra';
        }

        return [
            'is_prabayar' => $isPrabayar,
            'is_pascabayar' => $isPascabayar,
            'is_telkom' => $isTelkom,
            'is_internet' => $isInternet,
            'utility_type' => $utilityType,
            'pra_signals' => $praSignals,
            'pasca_signals' => $pascaSignals,
        ];
    }

    /**
     * Parse raw OCR text into structured receipt data with intelligent structure-awareness
     */
    public function parse(string $rawText): array
    {
        $cleanText = $this->normalizeText($rawText);

        // 1. Detect receipt structure (Prabayar vs Pascabayar vs Internet vs Telkom)
        $structure = $this->detectUtilityStructure($cleanText);

        // 2. Smart match against the 9 Master Utility Profiles
        $matchedProfile = $this->matchProfile($cleanText, $structure);
        if ($matchedProfile) {
            $structure['utility_type'] = $matchedProfile->category;
            if ($matchedProfile->category === 'listrik_pra') {
                $structure['is_prabayar'] = true;
                $structure['is_pascabayar'] = false;
            } elseif ($matchedProfile->category === 'listrik_pasca') {
                $structure['is_pascabayar'] = true;
                $structure['is_prabayar'] = false;
            }
        }

        // 3. Extract transaction date & time
        $transactionDate = $this->extractTransactionDate($cleanText);

        // 4. Extract billing month and fiscal year (especially from BL/TH for Pascabayar)
        $billingPeriod = $this->extractBillingMonthAndYear($cleanText, $transactionDate);
        $billingMonth = $billingPeriod['billing_month'];
        $fiscalYear = $billingPeriod['fiscal_year'];

        // 5. Extract token: MUST BE NULL if Pascabayar, Telkom, or Internet!
        $isNonPra = $structure['is_pascabayar'] || $structure['is_telkom'] || $structure['is_internet'];
        $tokenNumber = $isNonPra ? null : $this->extractTokenNumber($cleanText, false);
        if ($tokenNumber && ! $isNonPra) {
            $structure['is_prabayar'] = true;
            $structure['utility_type'] = 'listrik_pra';
        }

        // 6. Extract stand meter if Pascabayar
        $standMeter = $structure['is_pascabayar'] ? $this->extractStandMeter($cleanText) : null;

        // 7. Extract amounts with structure awareness
        $amounts = $this->extractAmounts($cleanText, $matchedProfile, $structure['is_pascabayar']);

        // 8. Extract other fields
        $tariffPower = $this->extractTariffPower($cleanText, $matchedProfile);
        $kwhAmount = $this->extractKwhAmount($cleanText);
        if ($kwhAmount === null && $standMeter !== null) {
            $kwhAmount = $standMeter['kwh'];
        }

        $customerInfo = $this->extractCustomerInfo($cleanText, $matchedProfile, $structure);
        $receiptNumber = $this->extractReceiptNumber($cleanText);
        $referenceNumber = $this->extractReferenceNumber($cleanText);
        $merchant = $this->extractMerchant($cleanText);

        $confidence = $this->calculateConfidence($matchedProfile, $amounts, $transactionDate, $tokenNumber, $structure['is_pascabayar']);

        return [
            'success' => $confidence >= 30,
            'confidence' => $confidence,
            'engine' => 'local_regex_parser',
            'data' => [
                'payment_merchant' => $merchant,
                'receipt_number' => $receiptNumber,
                'reference_number' => $referenceNumber,
                'transaction_date' => $transactionDate,
                'fiscal_year' => $fiscalYear,
                'billing_month' => $billingMonth,
                'utility_type' => $structure['utility_type'],
                'meter_number' => $customerInfo['meter_number'],
                'customer_number' => $customerInfo['customer_number'],
                'customer_name' => $customerInfo['customer_name'],
                'tariff_power' => $tariffPower,
                'kwh_amount' => $kwhAmount,
                'stand_meter' => $standMeter ? "{$standMeter['awal']}-{$standMeter['akhir']}" : null,
                'base_amount' => $amounts['base_amount'],
                'tax_pbjt' => $amounts['tax_pbjt'],
                'tax_ppn' => $amounts['tax_ppn'],
                'admin_fee' => $amounts['admin_fee'],
                'total_amount' => $amounts['total_amount'],
                'is_amount_verified' => $amounts['is_amount_verified'] ?? true,
                'expected_total' => $amounts['expected_total'] ?? $amounts['total_amount'],
                'amount_discrepancy' => $amounts['amount_discrepancy'] ?? 0,
                'token_number' => $tokenNumber,
                'matched_profile_id' => $matchedProfile?->id,
                'matched_profile_code' => $matchedProfile?->code,
                'matched_profile_name' => $matchedProfile?->name,
                'raw_text_snippet' => mb_substr($rawText, 0, 500),
            ],
        ];
    }

    /**
     * Match text against 9 Utility Profiles with fuzzy matching & structure awareness
     */
    public function matchProfile(string $text, ?array $structure = null): ?UtilityProfile
    {
        $profiles = UtilityProfile::where('is_active', true)->get();
        if ($profiles->isEmpty()) {
            return null;
        }

        $upperText = strtoupper($text);
        $disambiguatedText = $this->disambiguateOcrDigits($upperText);

        // 1. Exact match by clean meter number
        foreach ($profiles as $profile) {
            if ($profile->meter_number) {
                $cleanMeter = preg_replace('/[^0-9]/', '', $profile->meter_number);
                if (strlen($cleanMeter) >= 8) {
                    if (str_contains($upperText, $cleanMeter) || str_contains($disambiguatedText, $cleanMeter)) {
                        return $profile;
                    }
                }
            }
        }

        // 2. Exact match by customer number / IDPEL / phone / bank account
        foreach ($profiles as $profile) {
            $candidates = array_filter([$profile->customer_number, $profile->idpel]);
            foreach ($candidates as $cand) {
                if (! empty($cand) && str_contains($upperText, strtoupper(trim($cand)))) {
                    return $profile;
                }

                $cleanCand = preg_replace('/[^0-9]/', '', $cand);
                if (strlen($cleanCand) >= 7) {
                    if (str_contains($upperText, $cleanCand) || str_contains($disambiguatedText, $cleanCand)) {
                        return $profile;
                    }

                    // Also check line-by-line with non-digits stripped
                    foreach (explode("\n", $disambiguatedText) as $line) {
                        $cleanLineDigits = preg_replace('/[^0-9]/', '', $line);
                        if (strlen($cleanLineDigits) >= 7 && str_contains($cleanLineDigits, $cleanCand)) {
                            return $profile;
                        }
                    }
                }
            }
        }

        // 3. Levenshtein fuzzy match on 10-12 digit numbers in OCR text (tolerance: 1 digit difference)
        preg_match_all('/\b([0-9A-Za-z]{9,13})\b/', $upperText, $rawTokens);
        if (! empty($rawTokens[1])) {
            foreach ($rawTokens[1] as $rawTok) {
                $cleanTok = preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($rawTok));
                $len = strlen($cleanTok);
                if ($len >= 10 && $len <= 13) {
                    foreach ($profiles as $profile) {
                        $profileIds = array_filter([
                            preg_replace('/[^0-9]/', '', $profile->meter_number ?: ''),
                            preg_replace('/[^0-9]/', '', $profile->customer_number ?: ''),
                            preg_replace('/[^0-9]/', '', $profile->idpel ?: ''),
                        ]);
                        foreach ($profileIds as $target) {
                            if (abs(strlen($target) - $len) <= 1) {
                                if (levenshtein($cleanTok, $target) <= 1) {
                                    return $profile;
                                }
                            }
                        }
                    }
                }
            }
        }

        // 4. Structure-aware keyword matching
        $isPra = $structure['is_prabayar'] ?? false;
        $isPasca = $structure['is_pascabayar'] ?? false;
        $isTelkom = $structure['is_telkom'] ?? false;
        $isInternet = $structure['is_internet'] ?? false;

        $keywordRules = [
            // STM Prabayar vs Pascabayar
            'token_stm_pgri' => [
                'keywords' => ['STM PGRI', '45038124785', '514031736766'],
                'preferred_type' => 'listrik_pra',
            ],
            'listrik_stm' => [
                'keywords' => ['GEDUNG STM', '514031084530', 'STM'],
                'preferred_type' => 'listrik_pasca',
            ],
            // SMK PGRI Kras Token
            'token_smk_kras' => [
                'keywords' => ['32118573479', '514031897945', 'SMK PGRI KRAS', 'SMK KRAS', 'SMK PGRI'],
                'preferred_type' => 'listrik_pra',
            ],
            // Masjid An Nur
            'listrik_masjid' => [
                'keywords' => ['514030561937', 'MASJID AN NUR', 'AN NUR', 'ANNUR', 'MASJID'],
                'preferred_type' => 'listrik_pasca',
            ],
            // SMP PGRI
            'listrik_smp' => [
                'keywords' => ['514030561945', 'SMP PGRI', 'SMP KRAS', 'SMP'],
                'preferred_type' => 'listrik_pasca',
            ],
            // Telkom Speedy Imam Sjadeli / Imam Sadeli
            'speedy_imam' => [
                'keywords' => ['000479487', '0354000479487', '0354-000479487', 'IMAM SADELI', 'IMAM SJADELI', 'SADELI', 'SJADELI'],
                'preferred_type' => 'telkom_speedy',
            ],
            // Telkom Speedy SMK
            'speedy_smk_kras' => [
                'keywords' => ['004740750', '0354004740750', '0354-004740750', 'SPEEDY SMK', 'TELKOM SMK', 'SMK PGRI KRAS'],
                'preferred_type' => 'telkom_speedy',
            ],
            // Menaksopal Link Nusantara
            'internet_menaksopal' => [
                'keywords' => ['3243258444', 'MENAKSOPAL LINK NUSANTAR', 'MENAKSOPAL', 'MENAK SOPAL', 'LINK NUSANTARA'],
                'preferred_type' => 'internet',
            ],
        ];

        // First pass: match with structure affinity
        foreach ($keywordRules as $code => $rule) {
            $matchesType = false;
            if ($isPra && $rule['preferred_type'] === 'listrik_pra') {
                $matchesType = true;
            } elseif ($isPasca && $rule['preferred_type'] === 'listrik_pasca') {
                $matchesType = true;
            } elseif ($isTelkom && $rule['preferred_type'] === 'telkom_speedy') {
                $matchesType = true;
            } elseif ($isInternet && $rule['preferred_type'] === 'internet') {
                $matchesType = true;
            }

            if ($matchesType) {
                foreach ($rule['keywords'] as $kw) {
                    if (str_contains($upperText, $kw) || str_contains($disambiguatedText, $kw)) {
                        $p = $profiles->firstWhere('code', $code);
                        if ($p) {
                            return $p;
                        }
                    }
                }
            }
        }

        // Second pass: general keyword matching
        foreach ($keywordRules as $code => $rule) {
            foreach ($rule['keywords'] as $kw) {
                if (str_contains($upperText, $kw) || str_contains($disambiguatedText, $kw)) {
                    $p = $profiles->firstWhere('code', $code);
                    if ($p) {
                        return $p;
                    }
                }
            }
        }

        // 5. Fuzzy string similarity matching for profile customer name (>= 80% similarity)
        $expectedCategory = $structure['utility_type'] ?? null;
        $sortedProfiles = $profiles->sortByDesc(fn ($p) => ($expectedCategory && $p->category === $expectedCategory) ? 1 : 0);

        $lines = explode("\n", $upperText);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (strlen($trimmed) < 4) {
                continue;
            }
            foreach ($sortedProfiles as $profile) {
                $targetName = strtoupper($profile->customer_name ?: '');
                if (strlen($targetName) >= 4) {
                    similar_text($trimmed, $targetName, $pct);
                    if ($pct >= 80) {
                        return $profile;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract 20-digit electricity token with OCR character disambiguation
     * (MUST return null if receipt is PLN Pascabayar!)
     */
    public function extractTokenNumber(string $text, bool $isPascabayar = false): ?string
    {
        if ($isPascabayar) {
            return null;
        }

        // 1. Look for 5 groups of 4 alphanumeric chars near STROOM / TOKEN
        if (preg_match('/(?:STROOM|TOKEN|STROM)\D*([0-9A-Za-z]{4})[-\s\.]+([0-9A-Za-z]{4})[-\s\.]+([0-9A-Za-z]{4})[-\s\.]+([0-9A-Za-z]{4})[-\s\.]+([0-9A-Za-z]{4})/i', $text, $m)) {
            $raw = "{$m[1]}{$m[2]}{$m[3]}{$m[4]}{$m[5]}";
            $clean = preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($raw));
            if (strlen($clean) === 20) {
                return substr($clean, 0, 4).' '.substr($clean, 4, 4).' '.substr($clean, 8, 4).' '.substr($clean, 12, 4).' '.substr($clean, 16, 4);
            }
        }

        // 2. Scan all 5 blocks of 4 alphanumeric chars in text
        if (preg_match_all('/\b([0-9A-Za-z]{4})[-\s\.]+([0-9A-Za-z]{4})[-\s\.]+([0-9A-Za-z]{4})[-\s\.]+([0-9A-Za-z]{4})[-\s\.]+([0-9A-Za-z]{4})\b/', $text, $allMatches, PREG_SET_ORDER)) {
            foreach ($allMatches as $m) {
                $raw = "{$m[1]}{$m[2]}{$m[3]}{$m[4]}{$m[5]}";
                $clean = preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($raw));
                if (strlen($clean) === 20) {
                    return substr($clean, 0, 4).' '.substr($clean, 4, 4).' '.substr($clean, 8, 4).' '.substr($clean, 12, 4).' '.substr($clean, 16, 4);
                }
            }
        }

        // 3. Fallback: any continuous 20 digits
        if (preg_match('/\b([0-9A-Za-z]{20})\b/', $text, $m)) {
            $clean = preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($m[1]));
            if (strlen($clean) === 20) {
                return substr($clean, 0, 4).' '.substr($clean, 4, 4).' '.substr($clean, 8, 4).' '.substr($clean, 12, 4).' '.substr($clean, 16, 4);
            }
        }

        return null;
    }

    /**
     * Extract billing month and year (especially from BL/TH on Pascabayar)
     */
    public function extractBillingMonthAndYear(string $text, ?string $transDate = null): array
    {
        $monthsMap = [
            '01' => 'Januari', '1' => 'Januari', 'JAN' => 'Januari', 'JANUARI' => 'Januari',
            '02' => 'Februari', '2' => 'Februari', 'FEB' => 'Februari', 'FEBRUARI' => 'Februari',
            '03' => 'Maret', '3' => 'Maret', 'MAR' => 'Maret', 'MARET' => 'Maret',
            '04' => 'April', '4' => 'April', 'APR' => 'April', 'APRIL' => 'April',
            '05' => 'Mei', '5' => 'Mei', 'MEI' => 'Mei',
            '06' => 'Juni', '6' => 'Juni', 'JUN' => 'Juni', 'JUNI' => 'Juni',
            '07' => 'Juli', '7' => 'Juli', 'JUL' => 'Juli', 'JULI' => 'Juli',
            '08' => 'Agustus', '8' => 'Agustus', 'AGT' => 'Agustus', 'AGU' => 'Agustus', 'AGUSTUS' => 'Agustus',
            '09' => 'September', '9' => 'September', 'SEP' => 'September', 'SEPTEMBER' => 'September',
            '10' => 'Oktober', 'OKT' => 'Oktober', 'OKTOBER' => 'Oktober',
            '11' => 'November', 'NOV' => 'November', 'NOVEMBER' => 'November',
            '12' => 'Desember', 'DES' => 'Desember', 'DESEMBER' => 'Desember',
        ];

        // 1. Look for explicit BL/TH or REK/BLN in Pascabayar receipts: e.g. BL/TH : JUL26 or BL/TH : 0726
        if (preg_match('/(?:BL\s*[\/\-]\s*TH|REK\s*[\/\-]\s*BLN|TAG\s*[\/\-]?\s*BLN|BULAN)\D*([A-Z]{3,8}|\d{1,2})[\s\/\-\.]*(\d{2,4})/i', $text, $m)) {
            $rawMonth = strtoupper(trim($m[1]));
            $rawYear = trim($m[2]);

            $monthName = $monthsMap[$rawMonth] ?? null;
            $year = strlen($rawYear) === 2 ? (int) ('20'.$rawYear) : (int) $rawYear;

            if ($monthName && $year >= 2020 && $year <= 2099) {
                return [
                    'billing_month' => $monthName,
                    'fiscal_year' => $year,
                ];
            }
        }

        // 2. Fallback to transaction date if available
        if ($transDate) {
            try {
                $dt = Carbon::parse($transDate);
                $mCode = $dt->format('m');

                return [
                    'billing_month' => $monthsMap[$mCode] ?? 'Januari',
                    'fiscal_year' => (int) $dt->format('Y'),
                ];
            } catch (\Exception $e) {
                // ignore
            }
        }

        $now = now();
        $currMonthCode = $now->format('m');

        return [
            'billing_month' => $monthsMap[$currMonthCode] ?? 'Januari',
            'fiscal_year' => (int) $now->format('Y'),
        ];
    }

    /**
     * Extract Stand Meter (stand awal & akhir) for PLN Pascabayar
     */
    public function extractStandMeter(string $text): ?array
    {
        if (preg_match('/(?:STAND\s*METER|STAND\s*MTR|STAND)\D*([0-9A-Za-z]{4,8})\s*[-–]\s*([0-9A-Za-z]{4,8})/i', $text, $m)) {
            $awal = (int) preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($m[1]));
            $akhir = (int) preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($m[2]));
            $kwh = max(0, $akhir - $awal);

            return [
                'awal' => $awal,
                'akhir' => $akhir,
                'kwh' => (float) $kwh,
            ];
        }

        return null;
    }

    /**
     * Extract transaction date & time
     */
    public function extractTransactionDate(string $text): ?string
    {
        // 0. ATM transfer pattern: TANGGAL : ... and WAKTU : ...
        if (preg_match('/TANGGAL\D*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4})/i', $text, $dMatch)) {
            $rawDate = $dMatch[1];
            $timePart = '12:00:00';
            if (preg_match('/WAKTU\D*([0-9]{1,2}[:\.][0-9]{2}(?:[:\.][0-9]{2})?)/i', $text, $tMatch)) {
                $rawTime = str_replace('.', ':', $tMatch[1]);
                $timeParts = explode(':', $rawTime);
                $hour = str_pad($timeParts[0], 2, '0', STR_PAD_LEFT);
                $min = str_pad($timeParts[1], 2, '0', STR_PAD_LEFT);
                $sec = isset($timeParts[2]) ? str_pad($timeParts[2], 2, '0', STR_PAD_LEFT) : '00';
                $timePart = "{$hour}:{$min}:{$sec}";
            }

            if (preg_match('/([0-9]{1,2})[\/\-\.]([0-9]{1,2})[\/\-\.]([0-9]{2,4})/', $rawDate, $dateParts)) {
                $day = str_pad($dateParts[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($dateParts[2], 2, '0', STR_PAD_LEFT);
                $year = $dateParts[3];
                if (strlen($year) === 2) {
                    $year = '20'.$year;
                }

                return "{$year}-{$month}-{$day} {$timePart}";
            }
        }

        // 1. Pattern DD/MM/YY HH:MM:SS or DD/MM/YYYY HH:MM:SS
        if (preg_match('/\b([0-9]{1,2})[\/\-\.]([0-9]{1,2})[\/\-\.]([0-9]{2,4})\s+([0-9]{1,2})[:\.]([0-9]{2})(?:[:\.]([0-9]{2}))?\b/', $text, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $m[3];
            if (strlen($year) === 2) {
                $year = '20'.$year;
            }
            $hour = str_pad($m[4], 2, '0', STR_PAD_LEFT);
            $min = str_pad($m[5], 2, '0', STR_PAD_LEFT);
            $sec = isset($m[6]) ? str_pad($m[6], 2, '0', STR_PAD_LEFT) : '00';

            return "{$year}-{$month}-{$day} {$hour}:{$min}:{$sec}";
        }

        // 2. Pattern DD-Bulan-YYYY or DD Bulan YYYY
        $monthPattern = '(?:Jan(?:uari)?|Feb(?:ruari)?|Mar(?:et)?|Apr(?:il)?|Mei|Jun(?:i)?|Jul(?:i)?|Agu(?:stus)?|Sep(?:tember)?|Okt(?:ober)?|Nov(?:ember)?|Des(?:ember)?)';
        if (preg_match('/\b(\d{1,2})[\s\-]+'.$monthPattern.'[\s\-]+(\d{4})(?:\s+(\d{1,2})[:\.](\d{2}))?/i', $text, $m)) {
            try {
                $parsed = Carbon::parse($m[0]);

                return $parsed->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // ignore
            }
        }

        // 3. Fallback date only DD/MM/YY or DD/MM/YYYY
        if (preg_match('/\b(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})\b/', $text, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = strlen($m[3]) === 2 ? '20'.$m[3] : $m[3];

            return "{$year}-{$month}-{$day} 12:00:00";
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * Extract receipt number / no resi / reference
     */
    public function extractReceiptNumber(string $text): ?string
    {
        // 1. Resi format: e.g. 64172-03/2026/805315
        if (preg_match('/\b(\d{5}[-\/]\d{2}[\/\-]\d{4}[\/\-]\d+)\b/', $text, $m)) {
            return $m[1];
        }

        // 2. NO. RESI : 12345 / RESI: 12345
        if (preg_match('/(?:RESI|NO\.?\s*RESI|NO\.?\s*BUKTI|NO\.?\s*TRANS)\D*([A-Z0-9\-\/]{4,30})/i', $text, $m)) {
            return trim($m[1]);
        }

        // 3. 5-6 digit slip number like 07205
        if (preg_match('/\b(\d{5,8})\b/', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Extract reference number / ATM ref
     */
    public function extractReferenceNumber(string $text): ?string
    {
        if (preg_match('/(?:REF(?:ERENSI)?|NO\.?\s*REF|PETUGAS|NO\.?\s*AGEN)\D*([A-Z0-9\-\/]{4,32})/i', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * Extract payment merchant / channel
     */
    public function extractMerchant(string $text): string
    {
        if (preg_match('/(?:PT\s*P?T?OS\s+INDONESIA|POS\s+INDONESIA|KPC\.\s*KRAS)[^\n]*/i', $text, $m)) {
            return 'PT POS INDONESIA (PERSERO) Kpc. Kras 64172';
        }

        if (preg_match('/(?:ATM\s+BRI|BRI\s+UNIT[^\n]*|BANK\s+BRI|ATM\s+LINK)/i', $text, $m)) {
            return trim($m[0]);
        }

        if (preg_match('/(?:BANK\s+JATIM|ATM\s+BCA|ATM\s+MANDIRI|PLN\s+MOBILE|PPOB[^\n]*)/i', $text, $m)) {
            return trim($m[0]);
        }

        if (preg_match('/(?:LOKASI\s*:\s*([^\n\r]+))/i', $text, $m)) {
            $loc = trim($m[1]);
            if (! empty($loc) && strlen($loc) >= 3 && ! str_contains(strtoupper($loc), '...')) {
                return $loc;
            }
        }

        if (preg_match('/TRANSFER\s*ATM/i', $text)) {
            return 'ATM Bank / Transfer ATM';
        }

        return 'Loket Pembayaran / Bank';
    }

    /**
     * Extract amounts (base, admin, tax, total) with structure awareness (Prabayar vs Pascabayar)
     */
    public function extractAmounts(string $text, ?UtilityProfile $profile = null, bool $isPascabayar = false): array
    {
        $base = 0;
        $admin = 0;
        $taxPbjt = 0;
        $taxPpn = 0;
        $rpBayar = 0;
        $total = 0;

        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            // TOTAL BAYAR / JUMLAH TAGIHAN / JUMLAH TRANSFER / JUMLAH
            if (preg_match('/(?:TOTAL\s*(?:RP|BAYAR)?|JUMLAH\s*TAGIHAN|JUMLAH\s*TRANSFER|DIBAYAR|TOTAL)\D*([0-9A-Za-z\.\,]+)/i', $line, $m)) {
                $val = $this->parseNumericValue($m[1]);
                if ($val > 5000 && $val > $total) {
                    $total = $val;
                }
            } elseif (preg_match('/(?:JUMLAH\s*(?:RP)?)\D*([0-9A-Za-z\.\,]+)/i', $line, $m)) {
                $val = $this->parseNumericValue($m[1]);
                if ($val > 5000 && $val > $total) {
                    $total = $val;
                }
            }

            // RP BAYAR (Struk Pos Prabayar/Prepaid atau Struk Loket PLN)
            if (preg_match('/(?:RP\.?\s*BAYAR|TOTAL\s*BAYAR|JUMLAH\s*BAYAR|^BAYAR\b)\s*[:=]?\s*(?:RP\.?\s*)?([0-9A-Za-z\.\,]+)/i', $line, $m)) {
                $val = $this->parseNumericValue($m[1]);
                if ($val > 5000) {
                    $rpBayar = $val;
                }
            }

            // ADMIN BANK / ADMIN POS / BIAYA ADMIN / ADMIN
            if (preg_match('/(?:ADMIN\s*POS|BIAYA\s*ADMIN|ADMIN\s*BANK|^ADMIN\b|ADMIN\s*:|ADM|BIAYA\s*LOKET)\D*([0-9A-Za-z\.\,]+)/i', $line, $m)) {
                $val = $this->parseNumericValue($m[1]);
                if ($val > 0 && $val < 100000) {
                    $admin = $val;
                }
            }

            // PAJAK PBJT-TL / PPJ (khusus Prabayar biasanya tertera eksplisit)
            if (preg_match('/(?:PBJT-TL|PBJT|PPJ|PAJAK)\D*([0-9A-Za-z\.\,]+)/i', $line, $m)) {
                $val = $this->parseNumericValue($m[1]);
                if ($val > 0 && $val < 1000000) {
                    $taxPbjt = $val;
                }
            }

            // PPN
            if (preg_match('/(?:^PPN|PPN\s*:)\D*([0-9A-Za-z\.\,]+)/i', $line, $m)) {
                $val = $this->parseNumericValue($m[1]);
                if ($val >= 0 && $val < 1000000) {
                    $taxPpn = $val;
                }
            }

            // POKOK / TAGIHAN LISTRIK / RUPIAH TOKEN
            if ($isPascabayar) {
                // On Pascabayar: RP TAG PLN / TAGIHAN PLN / RP TAGIHAN
                if (preg_match('/(?:RP\s*TAG\s*PLN|TAGIHAN\s*(?:PLN)?|RP\s*TAG\s*(?:PLN|IHAN)?|RP\s*TAGIHAN|NILAI\s*TAGIHAN)\D*([0-9A-Za-z\.\,]+)/i', $line, $m)) {
                    $val = $this->parseNumericValue($m[1]);
                    if ($val > 5000) {
                        $base = $val;
                    }
                }
            } else {
                // On Prabayar & Telkom & Internet: RP STROOM/TOKEN / RUPIAH TOKEN / RP TOKEN / TAGIHAN
                if (! preg_match('/JUMLAH/i', $line) && preg_match('/(?:RP\s*STROOM(?:\s*[\/\-]?\s*TOKEN)?|RUPIAH\s*TOKEN|RP\s*TOKEN|NILAI\s*TOKEN|BELI\s*STROOM|NILAI\s*POKOK|TRANSFER|^TAGIHAN\b|TAGIHAN\s*:)\D*([0-9A-Za-z\.\,]+)/i', $line, $m)) {
                    $val = $this->parseNumericValue($m[1]);
                    if ($val > 5000) {
                        $base = $val;
                    }
                }
            }
        }

        // Resolusi Total Amount:
        // Pada Listrik Prabayar dan struk pembayaran, "RP BAYAR" adalah Total Bayar (total_amount).
        // Biaya admin tidak boleh ditambahkan lagi ke RP BAYAR karena sudah merupakan total akhir yang dibayarkan.
        if ($rpBayar > 0) {
            $total = $rpBayar;
        }

        // If total is still 0, search for large currency pattern
        if ($total === 0) {
            preg_match_all('/(?:RP\.?\s*)?([0-9A-Za-z]{1,3}(?:[.,][0-9A-Za-z]{3})+(?:[.,]\d{2})?|\d{4,8})\b/i', $text, $allNumbers);
            if (! empty($allNumbers[1])) {
                $parsedNumbers = [];
                foreach ($allNumbers[1] as $numStr) {
                    $val = $this->parseNumericValue($numStr);
                    if ($val >= 10000 && $val <= 20000000) {
                        $parsedNumbers[] = $val;
                    }
                }
                if (! empty($parsedNumbers)) {
                    $total = max($parsedNumbers);
                }
            }
        }

        if ($base === 0 && $total > 0) {
            $base = max(0, $total - $admin - $taxPbjt);
        }

        if ($total === 0 && $base > 0) {
            $total = $base + $admin + $taxPbjt;
        }

        // Verifikasi Aritmatika: Tagihan Pokok + Admin + Pajak vs Total Bayar
        $expectedTotal = $base + $admin + $taxPbjt;
        $isVerified = ($total > 0 && $expectedTotal > 0 && abs($expectedTotal - $total) < 1);
        $discrepancy = $total - $expectedTotal;

        // Toleransi jika total persis sama dengan base + taxPbjt (contoh: struk token murni tanpa biaya admin bank/pos)
        if (! $isVerified && $total > 0 && ($base + $taxPbjt) > 0 && abs($total - ($base + $taxPbjt)) < 1) {
            $expectedTotal = $base + $taxPbjt;
            $isVerified = true;
            $discrepancy = 0;
        }

        // Auto-rekonsiliasi jika $total persis sama dengan $base padahal ada $admin > 0
        // (contoh: OCR mengambil baris TAGIHAN sebagai TOTAL BAYAR sebelum menambahkan admin)
        if (! $isVerified && $total > 0 && $total === $base && $admin > 0) {
            $patternFormatted = number_format($expectedTotal, 0, '', '[.,]?');
            if (preg_match('/(?:RP\.?\s*)?'.$patternFormatted.'/i', $text) || str_contains($text, (string) (int) $expectedTotal)) {
                $total = $expectedTotal;
                $isVerified = true;
                $discrepancy = 0;
            }
        }

        return [
            'base_amount' => $base,
            'admin_fee' => $admin,
            'tax_pbjt' => $taxPbjt,
            'tax_ppn' => $taxPpn,
            'total_amount' => $total,
            'is_amount_verified' => $isVerified,
            'expected_total' => $expectedTotal,
            'amount_discrepancy' => $discrepancy,
        ];
    }

    /**
     * Extract tariff and power
     */
    public function extractTariffPower(string $text, ?UtilityProfile $profile = null): ?string
    {
        if (preg_match('/\b([SRBIP]\d{1,2}\s*[\/\-]?\s*\d+(?:\s*VA|\s*KVA)?)\b/i', $text, $m)) {
            return strtoupper(trim($m[1]));
        }

        if ($profile && $profile->tariff_power) {
            return $profile->tariff_power;
        }

        return null;
    }

    /**
     * Extract kWh amount
     */
    public function extractKwhAmount(string $text): ?float
    {
        if (preg_match('/(?:JML\s*KWH|KWH|ENERGY)\D*(\d+(?:[\.\,]\d+)?)\b/i', $text, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        return null;
    }

    /**
     * Extract customer info (meter, customer number, customer name)
     */
    public function extractCustomerInfo(string $text, ?UtilityProfile $profile = null, ?array $structure = null): array
    {
        $meterNumber = $profile?->meter_number;
        $customerNumber = ($profile?->category === 'listrik_pra' && $profile?->idpel)
            ? $profile->idpel
            : ($profile?->customer_number ?: $profile?->idpel);
        $customerName = $profile?->customer_name;

        $isPra = $structure['is_prabayar'] ?? true;

        // On Prabayar, extract meter number if not in profile
        if (! $meterNumber && $isPra) {
            if (preg_match('/(?:NO\.?\s*METER|METER|NO\.?\s*SERI)\D*([0-9A-Za-z]{10,14})\b/i', $text, $m)) {
                $clean = preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($m[1]));
                if (strlen($clean) >= 10) {
                    $meterNumber = $clean;
                }
            }
        }

        // When IDPEL is explicitly printed on receipt, prioritize IDPEL as customer number
        if (preg_match('/(?:IDPEL|ID\s*PELANGGAN)\D*([0-9A-Za-z]{8,14})\b/i', $text, $m)) {
            $clean = preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($m[1]));
            if (strlen($clean) >= 8) {
                $customerNumber = $clean;
            }
        }

        // Extract IDPEL / No Telp / Rekening
        if (! $customerNumber) {
            // Check specific telephone number first (e.g. NOMOR TELEPON : 0354-004740750)
            if (preg_match('/(?:NOMOR\s*TELEPON|NO\.?\s*TELP(?:ON)?)\D*([0-9\s-]{9,18})/i', $text, $m)) {
                $cleanPhone = trim($m[1]);
                if (strlen(preg_replace('/[^0-9]/', '', $cleanPhone)) >= 8) {
                    $customerNumber = $cleanPhone;
                }
            } elseif (preg_match('/(?:REKENING\s*TUJUAN|REKENING)\D*([0-9A-Za-z]{8,14})\b/i', $text, $m)) {
                $clean = preg_replace('/[^0-9]/', '', $this->disambiguateOcrDigits($m[1]));
                if (strlen($clean) >= 8) {
                    $customerNumber = $clean;
                }
            }
        }

        // Extract customer name if not resolved from profile
        if (! $customerName) {
            if (preg_match('/(?:KEPADA[\s\S]*?NAMA|PENERIMA)\D*([A-Z\s\.\/]{3,40})/i', $text, $m)) {
                $name = trim($m[1]);
                if (! in_array(strtoupper($name), ['RP', 'TOTAL', 'BAYAR', 'TRANSAKSI', 'POS', 'PLN', 'TAGIHAN', 'BANK', 'BCA', 'BRI'])) {
                    $customerName = $name;
                }
            } elseif (preg_match('/(?:NAMA\s*PELANGGA[N]?|NAMA|CUSTOMER)\D*([A-Z\s\.\/]{3,40})/i', $text, $m)) {
                $name = trim($m[1]);
                if (! in_array(strtoupper($name), ['RP', 'TOTAL', 'BAYAR', 'TRANSAKSI', 'POS', 'PLN', 'TAGIHAN', 'BANK'])) {
                    $customerName = $name;
                }
            }
        }

        return [
            'meter_number' => $meterNumber,
            'customer_number' => $customerNumber,
            'customer_name' => $customerName ?: ($profile?->name ?? 'Tagihan Utilitas'),
        ];
    }

    /**
     * Calculate confidence score (0-100)
     */
    protected function calculateConfidence(?UtilityProfile $profile, array $amounts, ?string $date, ?string $token, bool $isPascabayar = false): int
    {
        $score = 0;
        if ($profile !== null) {
            $score += 40;
        }
        if ($amounts['total_amount'] > 0) {
            $score += 35;
        }
        if (! empty($date)) {
            $score += 15;
        }
        if (! empty($token) && ! $isPascabayar) {
            $score += 20;
        } elseif ($isPascabayar) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Normalize OCR text (standardize spaces and linebreaks)
     */
    protected function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);

        return $text;
    }

    /**
     * Parse numeric string into integer amount
     */
    protected function parseNumericValue(string $numStr): int
    {
        $clean = trim($numStr);
        $clean = $this->disambiguateOcrDigits($clean);

        if (substr_count($clean, '.') > 1 || (substr_count($clean, '.') === 1 && strlen(substr($clean, strrpos($clean, '.') + 1)) === 3)) {
            $clean = str_replace('.', '', $clean);
        }

        if (substr_count($clean, ',') > 1 || (substr_count($clean, ',') === 3)) {
            $clean = str_replace(',', '', $clean);
        }

        $clean = preg_replace('/[,.]00$/', '', $clean);
        $clean = preg_replace('/[^0-9]/', '', $clean);

        return (int) $clean;
    }
}
