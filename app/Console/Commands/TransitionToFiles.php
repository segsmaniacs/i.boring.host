<?php

namespace App\Console\Commands;

use App\Helpers\SeaweedStorage;
use App\Helpers\WebDav;
use App\Models\File;
use App\Models\Image;
use Illuminate\Console\Command;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransitionToFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transition-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transition to files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Image::where('active', 1)->chunk(100, function($images) {
             foreach ($images as $image) {
                 try {
                     $content = file_get_contents(env('IMAGE_CDN') . '/' . $image->code . '.' . $image->extension);
                 } catch (\Exception $e) {
                     $content = false;
                 }

                 if ($content) {
                     $hash = sha1(
                         file_get_contents(env('IMAGE_CDN') . '/' . $image->code . '.' . $image->extension)
                     );

                     $file = File::where('sha1_hash', $hash)->where('size', $image->size)->first();
                     if (!$file) {
                         $file = File::create([
                             'sha1_hash' => $hash,
                             'size' => $image->size,
                             'location' => $image->image,
                             'thumbnail_location' => $image->thumbnail,
                             'stack_location' => $image->stack_location ? $image->stack_location : null
                         ]);
                     } else {

                         if ($image->stack_location) {
                             (new WebDav())->deleteFile($image->stack_location);
                             $image->stack_location = null;

                             $image->save();
                         }

                         $seaweedStorage = new SeaweedStorage();
                         $seaweedStorage->delete($image->image);
                         $seaweedStorage->delete($image->thumbnail);
                     }
                     $image->file_id = $file->id;
                     $image->save();
                 }
             }
        });
    }
}
