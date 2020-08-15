<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FilesController extends Controller
{
    const VIDEO_FILENAME = "full.webm";
    const USER_LOG_FILENAME = "click-log.txt";

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
            "files" => "required|array",
            "files.*" => "file"
        ]);
    }

    private function storeFiles(string $folder, UploadedFile ...$files)
    {
        foreach ($files as $file) {
//            $file->storeAs($folder, $file->getClientOriginalName(), "data");
            $path = $folder . "/" . $file->getClientOriginalName();
            $content = $file->get();
            Storage::disk("data")->append($path, $content);
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


    public function downloadData(Request $request, $key)
    {
        if (Storage::disk("data")->missing($key)) {
            return response()->json(["error" => "Data not found"], 404);
        }
        if (Storage::disk("data")->missing($key . "/full.webm")
            && Storage::disk("data")->missing($key . "/click-log.txt"))
        {
            return response()->json(["error" => "Data files not found"], 404);
        }

        $zip = new ZipArchive();
        $zipFileName = Storage::disk("data")->path($key . "/data.zip");
        if ($zip->open($zipFileName, ZipArchive::CREATE) == TRUE) {
            $videoFileRelativePath = $key . "/" . self::VIDEO_FILENAME;
            if (Storage::disk("data")->exists($videoFileRelativePath)) {
                $zip->addFile(
                    Storage::disk("data")->path($videoFileRelativePath),
                    "video.webm");
            }
            $userLogRelativePath = $key . "/" . self::USER_LOG_FILENAME;
            if (Storage::disk("data")->exists($userLogRelativePath)) {
                $zip->addFile(
                    Storage::disk("data")->path($userLogRelativePath),
                    "activity-log.txt");
            }

            $zip->close();
        }

        return response()->download($zipFileName);
    }
}
