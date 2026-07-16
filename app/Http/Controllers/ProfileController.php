<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProfileController extends Controller
{
    private const DEFAULT_BODY = <<<TXT
Dear {recruiter_name},

I am writing to apply for the {job_title} position at {company}. I have attached my resume and cover letter for your review.

I would welcome the opportunity to discuss how my experience fits this role. Thank you for your time and consideration.

Best regards,
{your_name}
TXT;

    public function edit()
    {
        return view('profile', [
            'profile'     => Profile::current(),
            'defaultBody' => self::DEFAULT_BODY,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'full_name'          => ['nullable', 'string', 'max:255'],
            'email'              => ['nullable', 'email', 'max:255'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'location'           => ['nullable', 'string', 'max:255'],
            'email_subject'      => ['required', 'string', 'max:255'],
            'email_body'         => ['required', 'string'],
            'resume'             => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'cover_letter'       => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'send_start_hour'    => ['nullable', 'integer', 'min:0', 'max:23'],
            'send_end_hour'      => ['nullable', 'integer', 'min:0', 'max:23'],
            'send_weekdays_only' => ['nullable'],
            'max_emails_per_hour'=> ['nullable', 'integer', 'min:0', 'max:1000'],
            'followup_days'      => ['nullable', 'integer', 'min:0', 'max:30'],
            'webhook_url'        => ['nullable', 'url', 'max:2048'],
            'preferred_role'     => ['nullable', 'string', 'max:255'],
            'preferred_sites'    => ['nullable', 'array'],
            'preferred_sites.*'  => ['string'],
        ]);

        $profile = Profile::current();

        $profile->fill([
            'full_name'          => $data['full_name'] ?? null,
            'email'              => $data['email'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'location'           => $data['location'] ?? null,
            'email_subject'      => $data['email_subject'],
            'email_body'         => $data['email_body'],
            'send_start_hour'    => $data['send_start_hour'] ?? null,
            'send_end_hour'      => $data['send_end_hour'] ?? null,
            'send_weekdays_only' => $request->boolean('send_weekdays_only'),
            'max_emails_per_hour'=> $data['max_emails_per_hour'] ?? 0,
            'followup_days'      => $data['followup_days'] ?? 0,
            'webhook_url'        => $data['webhook_url'] ?? null,
            'preferred_role'     => $data['preferred_role'] ?? null,
            'preferred_sites'    => $data['preferred_sites'] ?? [],
        ]);

        if ($request->hasFile('resume')) {
            $this->deleteIfExists($profile->resume_path);
            $file = $request->file('resume');
            $profile->resume_path = $file->store('documents');
            $profile->resume_name = $file->getClientOriginalName();
        }

        if ($request->hasFile('cover_letter')) {
            $this->deleteIfExists($profile->cover_letter_path);
            $file = $request->file('cover_letter');
            $profile->cover_letter_path = $file->store('documents');
            $profile->cover_letter_name = $file->getClientOriginalName();
        }

        $profile->save();

        return redirect()->route('profile.edit')->with('status', 'Profile saved.');
    }

    /**
     * Parse the uploaded resume to extract name and email.
     */
    public function parseResume(Request $request)
    {
        $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $file = $request->file('resume');
        $ext  = strtolower($file->getClientOriginalExtension());
        $text = '';

        if ($ext === 'docx') {
            $text = $this->extractTextFromDocx($file->getRealPath());
        } elseif ($ext === 'pdf') {
            $text = $this->extractTextFromPdf($file->getRealPath());
        } elseif ($ext === 'doc') {
            $text = $this->extractTextFromDoc($file->getRealPath());
        }

        $extracted = [
            'name'  => null,
            'email' => null,
            'phone' => null,
        ];

        // Extract email
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            $extracted['email'] = $m[0];
        }

        // Extract phone
        if (preg_match('/(?:\+?\d{1,3}[\s\-]?)?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{4}/', $text, $m)) {
            $extracted['phone'] = $m[0];
        }

        // Extract name (first non-empty line that looks like a name)
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        foreach ($lines as $line) {
            // Skip lines that look like email/phone/url
            if (preg_match('/@|http|www\.|\.com|\.org|\d{5,}/', $line)) continue;
            // A name is typically 2-4 words, letters only
            if (preg_match('/^[A-Z][a-zA-Z\'\-]+(?:\s+[A-Z][a-zA-Z\'\-]+){1,3}$/', $line)) {
                $extracted['name'] = $line;
                break;
            }
        }

        return response()->json($extracted);
    }

    private function extractTextFromDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return '';

        $content = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$content) return '';

        // Strip XML tags to get text
        $content = str_replace('</w:p>', "\n", $content);
        return strip_tags($content);
    }

    private function extractTextFromPdf(string $path): string
    {
        $content = file_get_contents($path);
        if (!$content) return '';

        $text = '';

        // Try to extract text from PDF streams
        if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Try zlib decompression
                $decoded = @gzuncompress($stream);
                if (!$decoded) $decoded = @gzinflate($stream);
                if (!$decoded) $decoded = $stream;

                // Extract text between parentheses (PDF text objects)
                if (preg_match_all('/\((.*?)\)/s', $decoded, $textMatches)) {
                    $text .= implode(' ', $textMatches[1]) . "\n";
                }

                // Extract text from Tj/TJ operators
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $decoded, $tjMatches)) {
                    foreach ($tjMatches[1] as $tj) {
                        if (preg_match_all('/\((.*?)\)/', $tj, $parts)) {
                            $text .= implode('', $parts[1]) . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        // Also try plain text extraction
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $btMatches)) {
            foreach ($btMatches[1] as $block) {
                if (preg_match_all('/\((.*?)\)/', $block, $textParts)) {
                    $text .= implode(' ', $textParts[1]) . "\n";
                }
            }
        }

        return $text;
    }

    private function extractTextFromDoc(string $path): string
    {
        $content = file_get_contents($path);
        if (!$content) return '';

        // Basic text extraction from .doc binary
        $text = '';
        $length = strlen($content);
        for ($i = 0; $i < $length; $i++) {
            $char = ord($content[$i]);
            if ($char >= 32 && $char <= 126) {
                $text .= chr($char);
            } elseif ($char === 13 || $char === 10) {
                $text .= "\n";
            }
        }

        return $text;
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
