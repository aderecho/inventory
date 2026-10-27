<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Streams a generated download as ndjson: one line per finished stage, then the
 * finished file as base64 so the browser can save it without a second request.
 * Used by the IIRUP print and the inventory stickers, so both report through
 * the same progress card.
 */
trait StreamsProgress
{
    /**
     * @param  callable(array, ?callable): array{filename: string, contents: string}  $generate
     */
    protected function streamProgress(Request $request, array $rules, callable $generate)
    {
        return response()->stream(function () use ($request, $rules, $generate) {
            $emit = function (array $event): void {
                echo json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            };

            try {
                $validated = Validator::make($request->all(), $rules)->validate();
            } catch (ValidationException $e) {
                $emit(['state' => 'error', 'message' => 'Invalid input.']);

                return;
            }

            $emit([
                'state' => 'working',
                'percent' => 2,
                'step' => 'Validated the request',
            ]);

            $onProgress = function (int $percent, string $step) use ($emit): void {
                $emit([
                    'state' => 'working',
                    'percent' => $percent,
                    'step' => $step,
                ]);
            };

            try {
                $result = $generate($validated, $onProgress);

                $emit([
                    'state' => 'completed',
                    'percent' => 100,
                    'step' => 'File ready',
                    'filename' => $result['filename'],
                    'file' => base64_encode($result['contents']),
                ]);
            } catch (ValidationException $e) {
                $emit(['state' => 'error', 'message' => 'Invalid input.']);
            } catch (\Throwable $e) {
                $emit(['state' => 'error', 'message' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
