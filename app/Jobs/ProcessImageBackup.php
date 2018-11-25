<?php

namespace App\Jobs;

use App\Helpers\SeaweedStorage;
use App\Models\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessImageBackup implements ShouldQueue
{
    protected $image;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Image $image)
    {
        $this->image = Image::where('id', $image->id)->first();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->image || !$this->image->active) {
            return;
        }

        $stackLocation = $this->image->storeImageToWebDav($this->image, $this->image->code, $this->image->extension,
            (new SeaweedStorage())->getImageContents($this->image)
        );

        $file = $this->image->file;
        $file->stack_location = $stackLocation;
        $file->save();

        return;
    }

}
