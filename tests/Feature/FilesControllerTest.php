<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilesControllerTest extends TestCase
{
    public function testSaveFile_whenKeyNotProvided()
    {
        Storage::fake("data");

        $response = $this->json("POST", "/api/files", [
            "files" => [UploadedFile::fake()->create("ble1.webm")]
        ]);

        echo $response->content();
        $response->assertStatus(422);
    }

    public function testSaveFile_whenFilesNotProvided()
    {
        Storage::fake("data");

        $response = $this->json("POST", "/api/files", [
            "key" => "a-uuid-string",
        ]);

        $response->assertStatus(422);
    }

    public function testSaveFiles_savesSingleFiles()
    {
        Storage::fake("data");

        $response = $this->json("POST", "/api/files", [
            "key" => "a-uuid-string",
            "files" => [UploadedFile::fake()->create("ble1.webm")]
        ]);

        $response->assertOk();
        Storage::disk("data")->assertExists("a-uuid-string/ble1.webm");
    }

    public function testSaveFiles_savesMultipleFiles()
    {
        Storage::fake("data");

        $response = $this->json("POST", "/api/files", [
            "files" => [
                UploadedFile::fake()->create("ble1.webm"),
                UploadedFile::fake()->create("ble2.webm"),
            ],
            "key" => "a-uuid-string"
        ]);

        $response->assertOk();
        Storage::disk("data")->assertExists("a-uuid-string/ble1.webm");
        Storage::disk("data")->assertExists("a-uuid-string/ble2.webm");
    }
}
