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
        $response->assertJson(["status" => "ok"]);
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
        $response->assertJson(["status" => "ok"]);
        Storage::disk("data")->assertExists("a-uuid-string/ble1.webm");
        Storage::disk("data")->assertExists("a-uuid-string/ble2.webm");
    }

    public function test_saveFileChunk_withoutKeyParam()
    {
        Storage::fake("data");

        $response = $this->json("POST", "api/file-chunk", [
            "chunk" => UploadedFile::fake()->createWithContent("ble.webm", "abcdef")
        ]);

        $response->assertStatus(422);
    }

    public function test_saveFileChunk_withoutChunkParam()
    {
        Storage::fake("data");

        $response = $this->json("POST", "api/file-chunk", [
            "key" => "a-uuid-string",
        ]);

        $response->assertStatus(422);
    }

    public function test_saveFileChunk_withChunkParamThatIsNotFile()
    {
        Storage::fake("data");

        $response = $this->json("POST", "api/file-chunk", [
            "key" => "a-uuid-string",
            "chunk" => "abcdef"
        ]);

        $response->assertStatus(422);
    }

    public function test_saveFileChunk()
    {
        Storage::fake("data");

        $response1 = $this->json("POST", "api/file-chunk", [
            "key" => "a-uuid-string",
            "chunk" => UploadedFile::fake()->createWithContent("ble.webm", "abcdef")
        ]);
        $response2 = $this->json("POST", "api/file-chunk", [
            "key" => "a-uuid-string",
            "chunk" => UploadedFile::fake()->createWithContent("ble.webm", "ghijkl")
        ]);

        $response1->assertOk();
        $response1->assertJson(["status" => "ok"]);
        $response2->assertOk();
        Storage::disk("data")->assertExists("a-uuid-string/full.webm");
        $this->assertEquals(
            "abcdefghijkl",
            Storage::disk("data")->get("a-uuid-string/full.webm")
        );
    }

    public function test_downloadData_withUnknownKey()
    {
        $response = $this->json(
            "GET", "/api/download-data/an-unknown-uuid-string");
        $response->assertNotFound();
    }

    public function test_downloadData_whenDataFilesNotPresent()
    {
        Storage::fake("data");
        Storage::disk("data")->makeDirectory("a-uuid-string");

        $response = $this->json(
            "GET", "/api/download-data/a-uuid-string");

        $response->assertNotFound();
        $response->assertJson(["error" => "Data files not found"]);
    }

    public function test_downloadData()
    {
        Storage::fake("data");
        Storage::disk("data")->put("a-uuid-string/full.webm", "abcd");
        Storage::disk("data")->put("a-uuid-string/click-log.txt", "abcd");

        $response = $this->json("GET", "/api/download-data/a-uuid-string");

        $response->assertOk();
    }
}
