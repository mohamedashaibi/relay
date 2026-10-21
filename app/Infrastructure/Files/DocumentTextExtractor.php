<?php

namespace App\Infrastructure\Files;

use App\Application\Attachments\TextExtractor;
use App\Domain\Attachments\AttachmentKind;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;
use ZipArchive;

/**
 * Pulls plain text out of uploads so assistants can read them:
 * text-like files, PDFs (smalot/pdfparser), and Word / PowerPoint (their XML, via ZipArchive).
 * Spreadsheets and images return null — images are sent to the model as images instead.
 */
final class DocumentTextExtractor implements TextExtractor
{
    private const MAX_CHARS = 500_000;

    public function extract(string $localPath, AttachmentKind $kind, string $name): ?string
    {
        try {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            $text = match (true) {
                $kind === AttachmentKind::Text => $this->plain($localPath),
                $kind === AttachmentKind::Pdf => (new Parser)->parseFile($localPath)->getText(),
                $ext === 'docx' => $this->office($localPath, fn (ZipArchive $zip) => [$zip->getFromName('word/document.xml')], '</w:p>'),
                $ext === 'pptx' => $this->office($localPath, fn (ZipArchive $zip) => $this->slides($zip), '</a:p>'),
                default => null,
            };
        } catch (Throwable $e) {
            Log::warning('Attachment text extraction failed', ['name' => $name, 'error' => $e->getMessage()]);

            return null;
        }

        return $this->normalize($text);
    }

    private function plain(string $path): string
    {
        $raw = (string) file_get_contents($path, length: self::MAX_CHARS * 4);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw); // UTF-8 BOM

        // Older Arabic files are often Windows-1256; fall back to Latin-1 for anything else.
        return mb_check_encoding($raw, 'UTF-8') ? $raw : (string) mb_convert_encoding($raw, 'UTF-8', 'Windows-1256,ISO-8859-1');
    }

    /** @param callable(ZipArchive): array<int,string|false> $parts */
    private function office(string $path, callable $parts, string $paragraphEnd): ?string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }

        $chunks = [];
        foreach ($parts($zip) as $xml) {
            if (is_string($xml)) {
                $xml = str_replace([$paragraphEnd, '<w:tab/>', '<w:br/>'], ["\n", "\t", "\n"], $xml);
                $chunks[] = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }
        $zip->close();

        return implode("\n\n", $chunks);
    }

    /** @return list<string|false> slide XML in slide order */
    private function slides(ZipArchive $zip): array
    {
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $entry, $m)) {
                $names[(int) $m[1]] = $entry;
            }
        }
        ksort($names);

        return array_map(fn ($n) => $zip->getFromName($n), array_values($names));
    }

    private function normalize(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $text);
        $text = (string) preg_replace("/[ \t]+\n/", "\n", $text);
        $text = trim((string) preg_replace("/\n{3,}/", "\n\n", $text));

        return $text === '' ? null : mb_substr($text, 0, self::MAX_CHARS);
    }
}
