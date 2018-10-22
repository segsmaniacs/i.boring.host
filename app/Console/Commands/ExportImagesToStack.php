<?php

namespace App\Console\Commands;

use App\Helpers\SeaweedStorage;
use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportImagesToStack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:stack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $startTime = time();

//        $imageCount = DB::select("SELECT count(*) as count FROM images WHERE active = 1")[0];
        $imageCount = DB::select("SELECT count(*) as count FROM images WHERE active = 1 AND id > 18870")[0];

        $pages = ceil($imageCount->count / 50);

//        for ($i = 1; $i < $pages+1; $i++)
        for ($i = 1; $i < $pages+1; $i++)
        {
            echo "processing badge: " . $i . " of images \n";
            $images = Image::where('active', 1)->skip(($i * 50 - 50) + 18870)->take(50)->get();

            foreach ($images as $image) {
                $stackLocation = $image->storeImageToWebDav($image, $image->code, $image->extension,
                    (new SeaweedStorage())->getImageContents($image)
                );
                $image->stack_location = $stackLocation;
                $image->save();

//                $this->line('uploaded ' . $image->id . ' to the stack');
            }
        }

        $took = time() - $startTime;
        echo "Done exporting images, took: " . $took . " seconds \n";
    }
}
