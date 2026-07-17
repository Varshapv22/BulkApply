<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

/**
 * Scores an uploaded resume for ATS (Applicant Tracking System) friendliness.
 * Pure-PHP heuristics — no external APIs. Each check returns pass / warn / fail
 * with an actionable tip; the weighted results roll up into a 0–100 score.
 */
class ResumeAtsAnalyzer
{
    private const ACTION_VERBS = [
        'developed', 'led', 'managed', 'built', 'created', 'designed', 'implemented', 'improved',
        'launched', 'delivered', 'increased', 'reduced', 'optimized', 'optimised', 'automated',
        'architected', 'migrated', 'deployed', 'integrated', 'streamlined', 'spearheaded',
        'coordinated', 'analyzed', 'analysed', 'established', 'achieved', 'trained', 'mentored',
        'collaborated', 'maintained', 'engineered', 'initiated', 'resolved', 'tested', 'debugged',
    ];

    /**
     * @return array{score:int, grade:string, categories:array, improvements:array, meta:array}
     */
    public function analyze(string $path, string $originalName, string $targetRole = ''): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $rawDocx = $ext === 'docx' ? $this->rawDocxXml($path) : '';
        $text = $this->extractText($path, $ext);
        $textLower = mb_strtolower($text);
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);

        $checks = [];

        /* ---------- File & format ---------- */
        $checks[] = $this->check('format', 'File format', 'File & Format', 10, match (true) {
            in_array($ext, ['pdf', 'docx'], true) => 'pass',
            default => 'warn',
        }, [
            'pass' => "Your .$ext file is a format every ATS can parse.",
            'warn' => "Old .$ext files confuse some systems — re-save your resume as PDF or DOCX.",
        ]);

        $checks[] = $this->check('extractable', 'Machine-readable text', 'File & Format', 15, match (true) {
            $wordCount >= 80 => 'pass',
            $wordCount >= 25 => 'warn',
            default => 'fail',
        }, [
            'pass' => 'Text extracts cleanly — ATS software can read your resume.',
            'warn' => 'Only a little text could be read. Parts of your resume may be images or unusual fonts — keep all content as selectable text.',
            'fail' => "Almost no text could be extracted — this looks like a scanned/image resume. ATS systems will see a blank page. Export a text-based PDF (in Word: File → Save As → PDF; never 'print to PDF' a scan).",
        ]);

        $generic = (bool) preg_match('/^(resume|cv|curriculum[\s_-]*vitae|final|document|untitled|new)[\s_-]*(\(?\d*\)?)?\.(pdf|docx?|)$/i', $originalName);
        $checks[] = $this->check('filename', 'File name', 'File & Format', 4, $generic ? 'warn' : 'pass', [
            'pass' => "“{$originalName}” is fine.",
            'warn' => "“{$originalName}” is generic. Recruiters download dozens of these — rename it like “Varsha_PV_Laravel_Developer.pdf”.",
        ]);

        if ($ext === 'docx' && $rawDocx !== '') {
            $hasTable = str_contains($rawDocx, '<w:tbl');
            $checks[] = $this->check('tables', 'Tables / text boxes', 'File & Format', 7, $hasTable ? 'warn' : 'pass', [
                'pass' => 'No tables detected — content flows in simple order, which ATS parsers prefer.',
                'warn' => 'Your DOCX contains tables. Many ATS parsers scramble or skip table content — move it into plain headings and bullet lists.',
            ]);
            $hasImage = str_contains($rawDocx, '<w:drawing') || str_contains($rawDocx, '<pic:');
            $checks[] = $this->check('images', 'Images / graphics', 'File & Format', 5, $hasImage ? 'warn' : 'pass', [
                'pass' => 'No embedded images — good; ATS ignores graphics anyway.',
                'warn' => 'Images/graphics detected. ATS can\'t read them — any skills or contact info inside a graphic is invisible. Keep visuals out or duplicate the info as text.',
            ]);
        }

        /* ---------- Contact info ---------- */
        $hasEmail = (bool) preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text);
        $checks[] = $this->check('email', 'Email address', 'Contact Info', 10, $hasEmail ? 'pass' : 'fail', [
            'pass' => 'Email address found.',
            'fail' => 'No email address detected in the resume text. Put it near the top in plain text (not inside a header image).',
        ]);

        $hasPhone = (bool) preg_match('/(\+?\d[\d\s\-().]{8,}\d)/', $text);
        $checks[] = $this->check('phone', 'Phone number', 'Contact Info', 6, $hasPhone ? 'pass' : 'warn', [
            'pass' => 'Phone number found.',
            'warn' => 'No phone number detected. Add it in plain text near your name.',
        ]);

        $hasLinked = str_contains($textLower, 'linkedin.com') || str_contains($textLower, 'github.com');
        $checks[] = $this->check('links', 'LinkedIn / GitHub link', 'Contact Info', 4, $hasLinked ? 'pass' : 'warn', [
            'pass' => 'Profile link found — recruiters use these to verify you.',
            'warn' => 'No LinkedIn or GitHub link found. Adding one increases recruiter trust and callbacks.',
        ]);

        /* ---------- Structure ---------- */
        $sections = [
            'experience' => '/\b(work\s+experience|professional\s+experience|experience|employment(\s+history)?)\b/i',
            'education'  => '/\b(education|academic|qualification)/i',
            'skills'     => '/\b(skills|technolog(y|ies)|technical\s+profici|core\s+competenc)/i',
        ];
        $found = [];
        foreach ($sections as $name => $re) {
            if (preg_match($re, $text)) $found[] = $name;
        }
        $missing = array_diff(array_keys($sections), $found);
        $checks[] = $this->check('sections', 'Standard section headings', 'Structure', 15, match (count($found)) {
            3 => 'pass', 2 => 'warn', default => 'fail',
        }, [
            'pass' => 'Experience, Education and Skills headings all found — ATS maps your content correctly.',
            'warn' => 'Missing heading(s): ' . implode(', ', array_map('ucfirst', $missing)) . '. ATS looks for these exact words to categorise your resume — add them as clear headings.',
            'fail' => 'Standard headings (Experience, Education, Skills) are mostly missing. ATS parsers rely on them — restructure with those exact section titles.',
        ]);

        $hasSummary = (bool) preg_match('/\b(summary|objective|profile|about\s+me)\b/i', $text);
        $checks[] = $this->check('summary', 'Professional summary', 'Structure', 4, $hasSummary ? 'pass' : 'warn', [
            'pass' => 'Summary/objective section found.',
            'warn' => 'No summary section. A 2–3 line professional summary at the top is prime space for role keywords.',
        ]);

        $checks[] = $this->check('length', 'Resume length', 'Structure', 6, match (true) {
            $wordCount >= 300 && $wordCount <= 1000 => 'pass',
            $wordCount >= 150 && $wordCount < 300 => 'warn',
            $wordCount > 1000 && $wordCount <= 1400 => 'warn',
            $wordCount < 150 => 'fail',
            default => 'warn',
        }, [
            'pass' => "~{$wordCount} words — a solid 1–2 page length.",
            'warn' => $wordCount < 300
                ? "~{$wordCount} words is thin. Aim for 400–800 words with concrete achievements."
                : "~{$wordCount} words is long. Trim to the most recent, relevant 1–2 pages.",
            'fail' => "Only ~{$wordCount} words were found — far too little content (or the text didn't extract).",
        ]);

        $hasDates = preg_match_all('/\b(19|20)\d{2}\b/', $text) >= 2;
        $checks[] = $this->check('dates', 'Employment dates', 'Structure', 5, $hasDates ? 'pass' : 'warn', [
            'pass' => 'Dates found — ATS can build your work timeline.',
            'warn' => 'Few or no year dates found. Give each role a “MMM YYYY – MMM YYYY” range so ATS can compute your experience.',
        ]);

        /* ---------- Content quality ---------- */
        $verbCount = 0;
        foreach (self::ACTION_VERBS as $v) {
            if (str_contains($textLower, $v)) $verbCount++;
        }
        $checks[] = $this->check('verbs', 'Action verbs', 'Content', 7, match (true) {
            $verbCount >= 5 => 'pass', $verbCount >= 2 => 'warn', default => 'fail',
        }, [
            'pass' => "Strong action verbs found ({$verbCount} distinct) — e.g. developed, led, implemented.",
            'warn' => "Only {$verbCount} action verb(s) found. Start each bullet with one: “Developed…”, “Led…”, “Reduced…”.",
            'fail' => 'No action verbs found. Rewrite duty-style lines (“responsible for…”) as achievements starting with strong verbs.',
        ]);

        $numbers = preg_match_all('/\b\d+(\.\d+)?\s*(%|percent|users|clients|projects|lakh|crore|k\b|x\b|hours?|days?)\b|\breduced\b|\bincreased\b/i', $text);
        $checks[] = $this->check('metrics', 'Quantified achievements', 'Content', 7, match (true) {
            $numbers >= 3 => 'pass', $numbers >= 1 => 'warn', default => 'fail',
        }, [
            'pass' => 'Measurable results found (numbers/percentages) — this is what recruiters scan for.',
            'warn' => 'Very few measurable results. Add numbers: “cut load time 40%”, “handled 200+ tickets/month”.',
            'fail' => 'No quantified results. Numbers make achievements believable — add at least 3 (%, ₹, counts, time saved).',
        ]);

        $bullets = preg_match_all('/^[\s]*[•·▪‣◦\-–*]/mu', $text);
        $checks[] = $this->check('bullets', 'Bullet points', 'Content', 4, $bullets >= 5 ? 'pass' : 'warn', [
            'pass' => 'Bullet lists detected — easy for both ATS and humans to scan.',
            'warn' => 'Few bullet points detected. Convert dense paragraphs into 1-line bullets.',
        ]);

        /* ---------- Role keyword match ---------- */
        if (trim($targetRole) !== '') {
            $roleTokens = array_values(array_filter(
                preg_split('/[\s\/,]+/', mb_strtolower($targetRole)),
                fn ($t) => mb_strlen($t) >= 3
            ));
            $hit = array_values(array_filter($roleTokens, fn ($t) => str_contains($textLower, $t)));
            $ratio = $roleTokens ? count($hit) / count($roleTokens) : 1;
            $missingKw = array_diff($roleTokens, $hit);
            $checks[] = $this->check('keywords', "Matches your target role (“{$targetRole}”)", 'Keywords', 10, match (true) {
                $ratio >= 0.99 => 'pass', $ratio >= 0.5 => 'warn', default => 'fail',
            }, [
                'pass' => 'Your resume mentions every word of your target role — ATS keyword filters will rank you well.',
                'warn' => 'Partly matches. Missing: ' . implode(', ', $missingKw) . '. Work these words into your summary and skills.',
                'fail' => 'Your target role words (' . implode(', ', $missingKw) . ') barely appear. ATS ranks by keyword match — mirror the job title and its key skills in your resume.',
            ]);
        }

        /* ---------- Roll up ---------- */
        $totalWeight = array_sum(array_column($checks, 'weight'));
        $earned = 0;
        foreach ($checks as $c) {
            $earned += $c['weight'] * match ($c['status']) { 'pass' => 1, 'warn' => 0.5, default => 0 };
        }
        $score = $totalWeight ? (int) round($earned / $totalWeight * 100) : 0;

        $categories = [];
        foreach ($checks as $c) {
            $categories[$c['category']][] = $c;
        }

        // Improvements: fails first (highest weight first), then warns.
        $improve = array_values(array_filter($checks, fn ($c) => $c['status'] !== 'pass'));
        usort($improve, fn ($a, $b) =>
            [$a['status'] === 'fail' ? 0 : 1, -$a['weight']] <=> [$b['status'] === 'fail' ? 0 : 1, -$b['weight']]);

        return [
            'score' => $score,
            'grade' => match (true) {
                $score >= 85 => 'Excellent', $score >= 70 => 'Good',
                $score >= 50 => 'Needs work', default => 'Poor',
            },
            'categories' => $categories,
            'improvements' => $improve,
            'meta' => ['words' => $wordCount, 'file' => $originalName, 'ext' => $ext],
        ];
    }

    private function check(string $id, string $label, string $category, int $weight, string $status, array $tips): array
    {
        return [
            'id' => $id, 'label' => $label, 'category' => $category,
            'weight' => $weight, 'status' => $status,
            'tip' => $tips[$status] ?? '',
        ];
    }

    /* ---------- Text extraction (same approach as ProfileController) ---------- */

    private function extractText(string $path, string $ext): string
    {
        return match ($ext) {
            'docx' => $this->fromDocx($path),
            'pdf' => $this->fromPdf($path),
            default => $this->fromDoc($path),
        };
    }

    private function rawDocxXml(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return '';
        $content = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();
        return $content;
    }

    private function fromDocx(string $path): string
    {
        $content = $this->rawDocxXml($path);
        if ($content === '') return '';
        $content = str_replace('</w:p>', "\n", $content);
        return strip_tags($content);
    }

    /**
     * Real PDF text extraction (handles compressed streams and font/ToUnicode
     * character maps, unlike a naive byte-level regex scan).
     */
    private function fromPdf(string $path): string
    {
        try {
            return (new PdfParser())->parseFile($path)->getText();
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function fromDoc(string $path): string
    {
        $content = @file_get_contents($path);
        if (!$content) return '';
        $text = '';
        $len = strlen($content);
        for ($i = 0; $i < $len; $i++) {
            $ch = ord($content[$i]);
            if (($ch >= 32 && $ch <= 126) || $ch === 10) $text .= chr($ch);
        }
        return $text;
    }
}
