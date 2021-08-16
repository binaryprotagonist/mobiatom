<?php

use App\Model\AssetTrackingPost;
use App\Model\AssetTrackingPostImage;
use Illuminate\Database\Seeder;

class AssetTrackingPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $salesman = array(338,359,376);

        for ($i = 1; $i < 500; $i++) {            
            $asset_tracking = new AssetTrackingPost;
            $asset_tracking->organisation_id = 61;
            $asset_tracking->asset_tracking_id = rand(1, 504);
            $asset_tracking->salesman_id = $salesman[array_rand($salesman)];
            $asset_tracking->feedback = $faker->text;
            $asset_tracking->save();
    
            if (is_object($asset_tracking)) {
                for ($h = 1; $h < 3; $h++) {
                    $asset_tracking_post_image = new AssetTrackingPostImage;
                    $asset_tracking_post_image->asset_tracking_id = $asset_tracking->asset_tracking_id;
                    $asset_tracking_post_image->asset_tracking_post_id = $asset_tracking->id;
                    $asset_tracking_post_image->image_string = 'https://mobiato-msfa.com/application-backend/public/uploads/asset-tracking-post/50225512180020-1601127771.jpeg';
                    $asset_tracking_post_image->save();
                }
            }
        }
    }
}
