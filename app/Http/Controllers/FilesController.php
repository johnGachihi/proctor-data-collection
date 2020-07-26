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

		return response()->json(["status" => "ok"]);
    }

    private function validateFileUploadRequest(Request $request)
    {
        $request->validate([
            "key" => "required",
            "files" => "required|array"
        ]);
    }

    private function storeFiles(string $folder, UploadedFile ...$files)
    {
        foreach ($files as $file) {
            $file->storeAs($folder, $file->getClientOriginalName(), "data");
        }
    }


    public function saveFileChunk(Request $request) {
        $this->validateSaveFileChunkRequest($request);

        $content = $request->file("chunk")->get();
        Storage::disk("data")->append(
            $request->key . "/full.webm", $content, null);

        return response()->json(["status" => "ok"]);
    }

    private function validateSaveFileChunkRequest(Request $request)
    {
        $request->validate([
            "key" => "required",
            "chunk" => "required|file"
        ]);
    }
}
