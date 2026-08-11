<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

abstract class Controller
{
    /**
     * Cek apakah ada file upload yang ditolak oleh batas server (upload_max_filesize).
     * Jika ya, redirect kembali dengan pesan error yang jelas.
     *
     * @param  array|string  $fields
     */
    protected function rejectOversizedUploads(Request $request, array|string $fields): ?RedirectResponse
    {
        foreach ((array) $fields as $field) {
            $files = $request->allFiles()[$field] ?? null;

            if ($files instanceof UploadedFile) {
                $files = [$files];
            }

            foreach ((array) $files as $file) {
                if ($file instanceof UploadedFile
                    && in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    $label = str_replace(['*', '_'], ['', ' '], $field);

                    return back()
                        ->withInput()
                        ->withErrors([$field => "Ukuran $label melebihi batas maksimal yang diizinkan server (" . ini_get('upload_max_filesize') . ').']);
                }
            }
        }

        return null;
    }
}
