<?php

namespace App\Services\Skills;

class SkillNormaliser
{
    /**
     * Only TRUE filler words. Never drop anything that could be a skill.
     */
    private const STOPWORDS = [
        // Grammar glue only — no skill meaning
        'a', 'an', 'the', 'and', 'or', 'of', 'in', 'on', 'at', 'to', 'for',
        'with', 'by', 'as', 'is', 'am', 'are', 'was', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should',
        'can', 'could', 'may', 'might', 'must', 'shall',
        'i', 'me', 'my', 'we', 'our', 'you', 'your', 'he', 'she', 'it',
        'its', 'they', 'them', 'this', 'that', 'these', 'those',
        'but', 'if', 'then', 'so', 'than', 'very', 'just', 'also',
        'some', 'any', 'all', 'each', 'every', 'no', 'not', 'only',
        'own', 'same', 'such', 'too', 'up', 'down', 'out', 'off',
        'over', 'under', 'again', 'further', 'once', 'here', 'there',
        'when', 'where', 'why', 'how', 'what', 'which', 'who', 'whom', 'whose',

        // Trailing "skill" suffix — always redundant
        'skill', 'skills',

        // Real filler phrases
        'passionate', 'passion', 'about', 'positive', 'growth', 'progress',
        'hardworking', 'dedicated', 'committed', 'motivated',
        'driven', 'oriented', 'focused', 'reliable', 'punctual',

        // Pure qualifiers
        'good', 'great', 'excellent', 'strong', 'basic', 'advanced',
        'intermediate', 'expert', 'high', 'low', 'level',
        'hold', 'holds', 'holding', 'possess', 'possesses',
        'demonstrate', 'demonstrates', 'proven', 'shown', 'shows',
        'years', 'year', 'related', 'relevant',
        'other', 'etc', 'plus', 'including', 'via', 'through', 'using',
    ];

    /**
     * Every word in the array maps to the KEY on the left.
     * Add liberally — false positives are cheap, false negatives are costly.
     */
    private const SYNONYMS = [
        // ── Communication / interpersonal ──────────────────────
        'communication' => [
            'communication', 'communicate', 'communicating', 'communicative',
            'interpersonal', 'people', 'social', 'relationship', 'rapport',
            'collaboration', 'collaborate', 'collaborative',
            'presentation', 'presenting', 'public', 'speaking',
        ],

        // ── Teamwork ───────────────────────────────────────────
        'teamwork' => [
            'teamwork', 'team', 'teams', 'cooperation', 'cooperative',
            'collaboration', 'collaborative',
        ],

        // ── Computer / IT ──────────────────────────────────────
        'computer' => [
            'computer', 'computers', 'computing',
            'proficiency', 'proficient',
            'ict', 'it', 'digital', 'literacy',
            'excel', 'spreadsheet', 'spreadsheets', 'ms-excel',
            'word', 'ms-word', 'microsoft-word',
            'powerpoint', 'ms-powerpoint', 'microsoft-powerpoint',
            'office', 'microsoft-office', 'ms-office',
        ],

        // ── Organisation / planning ────────────────────────────
        'organisation' => [
            'organisation', 'organization', 'organisational', 'organizational',
            'organise', 'organize', 'organised', 'organized',
            'organising', 'organizing', 'planning', 'planner',
            'schedule', 'scheduling', 'prioritise', 'prioritize',
            'timemanagement', 'time',
        ],

        // ── Detail / accuracy ──────────────────────────────────
        'detail' => [
            'detail', 'detail-oriented', 'detailed', 'details',
            'meticulous', 'thorough', 'accurate', 'accuracy',
            'precision', 'precise', 'careful',
        ],

        // ── Problem solving / analysis ─────────────────────────
        'analysis' => [
            'analysis', 'analytical', 'analyze', 'analyse', 'analysing', 'analyzing',
            'problem', 'problems', 'solving', 'solution', 'solutions',
            'critical', 'criticalthinking', 'reasoning',
        ],

        // ── Customer service ───────────────────────────────────
        'customer' => [
            'customer', 'customers', 'client', 'clients',
            'customerservice', 'customercare', 'customersupport',
            'customersrelations', 'service', 'services',
            'front', 'frontdesk', 'reception', 'receptionist',
            'hospitality', 'guest', 'guests',
            'sales', 'selling', 'sell',
            'marketing', 'market',
        ],

        // ── Administration ─────────────────────────────────────
        'administrative' => [
            'administrative', 'administration', 'admin',
            'clerical', 'filing', 'records', 'recordkeeping',
            'data', 'dataentry', 'typing',
            'documentation', 'document', 'documenting',
            'report', 'reports', 'reporting',
        ],

        // ── Numeric / finance ──────────────────────────────────
        'numeracy' => [
            'numeracy', 'numerical', 'numeric', 'numbers',
            'mathematical', 'mathematics', 'math', 'maths', 'arithmetic',
            'accounting', 'account', 'accounts', 'accountant',
            'financial', 'finance', 'finances',
            'credit', 'creditanalysis', 'creditanalyst',
            'risk', 'riskmanagement', 'riskanalysis',
            'loan', 'loans', 'lending', 'underwriting', 'compliance',
            'audit', 'auditing', 'auditor',
        ],

        // ── Healthcare ─────────────────────────────────────────
        'clinical' => [
            'clinical', 'clinic', 'patient', 'patients',
            'nursing', 'nurse', 'nurses', 'midwife', 'midwifery',
            'medication', 'medications', 'drug', 'drugs',
            'pharmacy', 'pharmaceutical', 'dispense', 'dispensing',
        ],

        // ── Teaching ───────────────────────────────────────────
        'teaching' => [
            'teaching', 'teach', 'teacher', 'trainer', 'training',
            'tutor', 'tutoring', 'mentor', 'mentoring',
            'curriculum', 'lesson', 'lessonplanning', 'syllabus',
        ],

        // ── Engineering / technical ────────────────────────────
        'engineering' => [
            'engineering', 'engineer', 'engineers', 'technical',
            'mechanical', 'electrical', 'civil',
            'construction', 'site', 'supervision',
        ],

        // ── Language ───────────────────────────────────────────
        'language' => [
            'language', 'languages', 'english', 'french', 'swahili',
            'luganda', 'acholi', 'langi', 'kumam', 'alur',
        ],
    ];

    public function canonicalise(string|array $raw): array
    {
        $phrases = $this->toPhrases($raw);
        $tokens  = [];

        foreach ($phrases as $phrase) {
            foreach ($this->phraseToTokens($phrase) as $token) {
                $tokens[$token] = true;
            }
        }

        return array_keys($tokens);
    }

    private function toPhrases(string|array $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }
        $raw = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/[,;|\n\r•·●▪\-\/]+/', $raw);
        return array_values(array_filter(array_map('trim', $parts)));
    }

    private function phraseToTokens(string $phrase): array
    {
        $phrase = strtolower(trim($phrase));
        $phrase = preg_replace('/[\/\\\\]+/', ' ', $phrase);
        $phrase = preg_replace('/-+/', ' ', $phrase);

        // Split words, keep alphanumeric
        $words = preg_split('/[^a-z0-9]+/', $phrase);
        $words = array_filter($words, fn($w) => $w !== '');

        // Keep stopwords-checked list but ALSO remember the originals
        // for compound matching
        $meaningful = array_filter(
            $words,
            fn($w) => !in_array($w, self::STOPWORDS, true)
        );

        // If nothing meaningful left, try matching the ORIGINAL phrase
        // against synonyms (e.g. "passionate about growth" might still
        // have "growth" as a mapped keyword somewhere)
        if (empty($meaningful)) {
            return [];
        }

        // Try the joined form first ("customerservice" from "customer service")
        $joined  = implode('', $meaningful);
        $hyphen  = implode('-', $meaningful);

        $canonical = [];

        // Pass 1 — try the full joined phrase
        foreach (self::SYNONYMS as $canon => $variants) {
            if (in_array($joined, $variants, true) || in_array($hyphen, $variants, true)) {
                $canonical[$canon] = true;
            }
        }

        // Pass 2 — try each meaningful word individually
        foreach ($meaningful as $word) {
            if (mb_strlen($word) < 2) continue;

            $matched = false;
            foreach (self::SYNONYMS as $canon => $variants) {
                if (in_array($word, $variants, true)) {
                    $canonical[$canon] = true;
                    $matched = true;
                }
            }

            // No synonym match → keep raw word so unknown domains still match
            if (!$matched) {
                $canonical[$word] = true;
            }
        }

        return array_keys($canonical);
    }
}