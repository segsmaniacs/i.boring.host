<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Storage;

class ExportImagesToSeaweed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:seaweed';

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

        $imageCount = DB::select("SELECT count(*) as count FROM images WHERE active = 1")[0];

        $pages = ceil($imageCount->count / 50);

        for ($i = 1; $i < $pages+1; $i++)
        {
            echo "processing badge: " . $i . " of images \n";
            $images = Image::where('active', 1)->skip($i * 50 - 50)->take(50)->get();

            foreach ($images as $image) {
                $disImage = false;
                if (file_exists('storage/images/' . $image->code . '.' . $image->extension)) {
                    $disImage = $image->storeImage('storage/images/' . $image->code . '.' . $image->extension);
                }
                $disThumbnail = false;
                if (file_exists('storage/images/thumbnail/' . $image->code . '.jpg')) {
                    $disThumbnail = $image->storeImage('storage/images/thumbnail/' . $image->code . '.jpg');
                }

                if ($disImage) {
                    $image->image = $disImage['assign']->fid;
                }
                if ($disThumbnail) {
                    $image->thumbnail = $disThumbnail['assign']->fid;
                }
                $image->save();

                Storage::disk('spaces')->put(explode(',', $image->image)[0] . '/' . $image->code . '.' . $image->extension, file_get_contents('storage/images/'. $image->code.'.'. $image->extension));
                //Storage::disk('spaces')->put(explode(',', $image->image)[0] . '/thumbnail/' . $image->code . '.jpg', file_get_contents('storage/images/thumbnail/'. $image->code.'.jpg'));
            }
        }

        $took = time() - $startTime;
        echo "Done exporting images, took: " . $took . " seconds \n";
    }
}
