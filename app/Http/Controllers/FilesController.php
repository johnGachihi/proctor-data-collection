<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function saveFiles(Request $request)
    {
        $this->validateFileUploadRequest($request);

        if ($request->hasFile("files")) {
            $this->storeFiles($request->key, ...$request->file("files"));
        }
    }

    public function validateFileUploadRequest(Request $request)
    {
        $request->validate([
            "key" => "required",
            "files" => "required|array"
        ]);
    }

    public function storeFiles(string $folder, UploadedFile ...$files)
    {
        foreach ($files as $file) {
            $file->storeAs($folder, $file->getClientOriginalName(), "data");
        }
    }
}
