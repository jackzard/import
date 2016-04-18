<?php
//            $urlSearch = 'http://maps.google.com/maps/api/geocode/json?address='.end($villalocation).'&sensor=false';
//
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $urlSearch);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//            $rsp = curl_exec($ch);
//
//            $results = json_decode($rsp,TRUE)['results'];
//
//            if (is_array($results)){
//                $results = $results[0];
//            }
//
//            $villaLatitude = $results['geometry']['location']['lat'];
//            $villaLongitude = $results['geometry']['location']['lng'];


Route::get('post/', function () {



    $posts = \App\Post::
    where('kibarer_posts.post_status', 'publish')
        ->with('Translate')
        ->with('Users.UsersMeta')
        ->with('Attachment')
//        ->with('PostMeta')
//        ->with('TermRelationships.TermTaxonomy.Terms')
        ->where('kibarer_posts.post_type', 'testimonials')
        ->groupBy('kibarer_posts.ID')

        ->get();



    foreach ($posts as $post) {
        $client = '';
        foreach ($post->PostMeta as $meta) {
            if ($meta->meta_key == 'client') {
                $client = $meta->meta_value;
            }

        }

        $testi = \DB::connection('new')
            ->table('testimonials')
            ->insert([
                'user_id' => $post->Users->ID,
                'customer_name' => $client,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'slug' => $post->post_name,
                'status' => 1
            ]);

        $testiID = \DB::connection('new')
                        ->table('testimonials')
            ->where('slug',$post->post_name)
            ->first();



        foreach ($post->Attachment as $att){

            $explode = str_replace('wp-content', '', explode('.com/', $att->guid));
            $directory = explode('/', $explode[1]);
            $imageName = end($directory);
            array_pop($directory);
            $directory = array_filter($directory);
            $pathDir = str_replace('~villabal','',implode('/', $directory));
            $pathDir = str_replace('/uploads','uploads',$pathDir);

            if (!\File::exists($pathDir)) {
                \File::makeDirectory($pathDir,0777,true,true);
            }
            if (!\File::exists($pathDir . '/thumb')) {
                \File::makeDirectory($pathDir . '/thumb',0777,true,true);
            }

            $ch = curl_init($att->guid);

            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($retcode == 200) {
                $remoteImages = file_get_contents($att->guid);
                $img = \Image::make($remoteImages);

                $big = $img->resize(1000, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $small = $img->resize(400, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $big->save($pathDir.'/'.$imageName);
                $small->save($pathDir.'/thumb/'.$imageName);
                $pathDir = str_replace('uploads','/uploads',$pathDir);
                \DB::connection('new')
                    ->table('attachments')
                    ->insert([
                        'attachable_id' => $testiID->id,
                        'attachable_type' => 'App\Testimony',
                        'file' => '/' . $pathDir.'/'.$imageName,
                        'thumb' => '/' . $pathDir.'/thumbs/'.$imageName,
                        'type' => 'img'
                    ]);
            }

        }


    }


});

Route::get('attach/{skip}',function($skip){
    $skip = $skip * 500;
    $posts = \App\Attachment::where('post_type','attachment')
        ->where('post_status','inherit')
        ->where('post_parent','>',1)
        ->with('translate')
        ->skip($skip)
        ->limit(500)
        ->get();


    foreach ($posts as $post){
        $explode = str_replace('wp-content', '', explode('.com/', $post->guid));

        $directory = explode('/', $explode[1]);
        $imageName = end($directory);
        array_pop($directory);
        $directory = array_filter($directory);
        $pathDir = implode('/', $directory);
        if (count($post->translate)){
            \DB::connection('new')
                ->table('attachments')
                ->insert([
                    'attachable_id' => $post->translate->trid,
                    'attachable_type' => 'App\Property',
                    'file' => $pathDir.'/'.$imageName,
                    'thumb' => $pathDir.'/'.$imageName,
                    'type' => 'img'
                ]);
        }

    }

});


Route::get('/{skip}', function ($skip) {

    $villaFacilities = [];
    $villaFacilitiesDesc = [];

    $villaPrice = '';
    $villaStatus = 'free hold';
    $villaBuildingSize = '';
    $villaLandSize = '';
    $villaBedroom = '';
    $villaBathroom = '';
    $villaCode = '';
    $villaMetaStatus = '';
    $villBuild = '';
    $villaEndYear = '';

    $villaViewsNorth = '';
    $villaViewsEast = '';
    $villaViewsSouth = '';
    $villaViewsWest = '';
    $villaPriceOnRequest = '';
    $villaIsExclusive = '';
    $villaOwnerName = '';
    $villaOwnerPhone = '';
    $villaOwnerCommision = '';
    $villaWhySell = '';
    $villaPeriode = '';
    $villaCurrency = '';
    $villaSellInFurnish = '';
    $villaInspectedBy = '';
    $villaContactForViewing = '';
    $villaOtherListing = '';



    $skip = $skip * 300;
    $posts = \App\Post::
    with('TermRelationships.TermTaxonomy.Terms')
        ->with('Users.UsersMeta')
        ->with('Attachment')
        ->with('PostMeta')
        ->with('Translate')
        ->where('kibarer_posts.post_status', 'publish')
        ->whereIn('kibarer_posts.post_type', ['bali-villa-for-sale', 'bali-land-for-sale'])
        ->groupBy('kibarer_posts.ID')
        ->skip($skip)
        ->limit(300)
        ->get();
    foreach ($posts as $post) {
        $content = $post->post_content;
        $title = $post->post_title;
        $slug = $post->post_name;


        if ($post->post_type == 'bali-villa-for-sale') {
            $category = 1;
        } else {
            $category = 3;
        }
        $villalocation = [];
        foreach ($post->TermRelationships as $term) {

            if ($term->TermTaxonomy->taxonomy == 'villa-area' OR $term->TermTaxonomy->taxonomy == 'area' OR $term->TermTaxonomy->taxonomy == 'land-area') {
                $villalocation[] = $term->TermTaxonomy->Terms->name;
            }

            if ($term->TermTaxonomy->taxonomy == 'villa-status') {
                $villaStatus = $term->TermTaxonomy->Terms->name;
                if ($villaStatus == 'LeaseHold') {
                    $villaStatus = 'lease hold';
                } else {
                    $villaStatus = 'free hold';
                }
            }

            if ($term->TermTaxonomy->taxonomy == 'villa-category') {
                $villaType[] = $term->TermTaxonomy->Terms->name;
            }


        }

        $cekuser = \DB::connection('new')
            ->table('users')
            ->where('id', $post->Users->ID)
            ->first();
        if (!count($cekuser)) {
            \DB::connection('new')
                ->table('users')
                ->insert([
                    'id' => $post->Users->ID,
                    'username' => $post->Users->user_login,
                    'email' => $post->Users->user_email,
                    'password' => $post->Users->user_pass,
                    'firstname' => $post->Users->display_name,
                    'role_id' => 4,
                    'branch_id' => 1,
                    'active' => 1,
                ]);
        }


        foreach ($post->Attachment as $file) {

            if ($file->post_type == 'attachment') {

                $explode = str_replace('wp-content', '', explode('.com/', $file->guid));

                $directory = explode('/', $explode[1]);
                $imageName = end($directory);
                array_pop($directory);
                $directory = array_filter($directory);
                $pathDir = implode('/', $directory);

//
//                if (!\File::exists($pathDir)) {
//                    \File::makeDirectory($pathDir, 0777, true, true);
//                    \File::makeDirectory($pathDir . '/thumb', 0777, true, true);
//                }

                $path[$file->ID] = $pathDir . '/' . $imageName;
                $pathThumb[$file->ID] = $pathDir . '/thumb/' . $imageName;
                $pathUrl[$file->ID] = $file->guid;
            }

        }


        foreach ($post->PostMeta as $meta) {

            if ($meta->meta_key == 'villa_code') {
                $villaCode = $meta->meta_value;
            }

            if ($meta->meta_key == 'price') {

                $villaPrice = $meta->meta_value;
            }

            if ($meta->meta_key == 'building_size') {
                $villaBuildingSize = $meta->meta_value;
            }

            if ($meta->meta_key == 'land_size') {
                $villaLandSize = $meta->meta_value;
            }

            if ($meta->meta_key == 'year_built') {
                $villBuild = $meta->meta_value;
            }

            if ($meta->meta_key == 'villa_code') {
                $villaCode = $meta->meta_value;
            }

            if ($meta->meta_key == 'periode') {
                $villaPeriode = $meta->meta_value;
            }

            if ($meta->meta_key == 'sell_in_furnish') {
                $villaSellInFurnish = $meta->meta_value;
            }

            if ($meta->meta_key == 'distance_beach') {
                $villaDistanceBeach = $meta->meta_value;
            }

            if ($meta->meta_key == 'distance_beach_type') {
                $villaDistanceBeachType = $meta->meta_value;
            }


            if ($meta->meta_key == 'distance_airport') {
                $villaDistanceAirport = $meta->meta_value;
            }

            if ($meta->meta_key == 'distance_airport_type') {
                $villaDistanceAirportType = $meta->meta_value;
            }


            if ($meta->meta_key == 'distance_market') {
                $villaDistanceMarket = $meta->meta_value;
            }

            if ($meta->meta_key == 'distance_market_type') {
                $villaDistanceMarketType = $meta->meta_value;
            }


            if ($meta->meta_key == 'views_north') {
                $villaViewsNorth = $meta->meta_value;
            }
            if ($meta->meta_key == 'views_east') {
                $villaViewsEast = $meta->meta_value;
            }
            if ($meta->meta_key == 'views_west') {
                $villaViewsWest = $meta->meta_value;
            }
            if ($meta->meta_key == 'views_south') {
                $villaViewsSouth = $meta->meta_value;
            }
            if ($meta->meta_key == 'name') {
                $villaOwnerName = $meta->meta_value;
            }
            if ($meta->meta_key == 'owner_name') {
                $villaOwnerName = $meta->meta_value;
            }
            if ($meta->meta_key == 'phone_number') {
                $villaOwnerPhone = $meta->meta_value;
            }
            if ($meta->meta_key == 'owner_phone') {
                $villaOwnerPhone = $meta->meta_value;
            }
            if ($meta->meta_key == 'owner_commision') {
                $villaOwnerCommision = $meta->meta_value;
            }
            if ($meta->meta_key == 'contact_for_viewing') {
                $villaContactForViewing = $meta->meta_value;

            }
            if ($meta->meta_key == 'document_received') {
                $villaDocumentReveived = unserialize($meta->meta_value);
                if ($villaDocumentReveived) {
                    foreach ($villaDocumentReveived as $doc) {
                        $cekMeta = \DB::connection('new')
                            ->table('property_metas')
                            ->where('property_id', $post->translate->trid)
                            ->where('name', $doc)
                            ->first();

                        if (!count($cekMeta)) {
                            \DB::connection('new')
                                ->table('property_metas')
                                ->insert([
                                    'property_id' => $post->translate->trid,
                                    'name' => $doc,
                                    'value' => 'ready',
                                    'type' => 'document',
                                    'status' => 1
                                ]);
                        }

                    }
                }

            }
            if ($meta->meta_key == 'why_seller_wants_to_sell') {
                $villaWhySell = $meta->meta_value;
            }
            if ($meta->meta_key == 'status') {
                $villaMetaStatus = $meta->meta_value;
                if ($villaMetaStatus == 'under-offer') {
                    $villaMetaStatus = 1;
                } else {
                    $villaMetaStatus = 0;
                }
            }

            if ($meta->meta_key == 'disappear') {
                $villaMetaStatus = -1;
            }


            if ($meta->meta_key == 'price_on_request') {
                $villaPriceOnRequest = $meta->meta_value;
            }

            if ($meta->meta_key == 'visited_by') {
                $villaInspectedBy = $meta->meta_value;
            }

            if ($meta->meta_key == 'other_listing_agent') {
                $villaOtherListing = $meta->meta_value;
            }


            if ($meta->meta_key == 'sold_date') {
                $villaSoldDate = $meta->meta_value;
            }


            if ($meta->meta_key == 'end_year') {
                $villaEndYear = $meta->meta_value;
            }

            if ($meta->meta_key == 'is_exclusive') {
                $villaIsExclusive = $meta->meta_value;
            }

            if ($meta->meta_key == 'images') {

                $villaImages = unserialize($meta->meta_value);
            }


            if (strpos($meta->meta_key, '_facilities_type') !== false) {

                if (strpos($meta->meta_value, 'field_') === false) {

                    $villaFacilities[str_replace('_type', '', $meta->meta_key)] = $meta->meta_value;


                }

            }


            if (strpos($meta->meta_key, '_facilities_description') !== false) {

                if (strpos($meta->meta_value, 'field_') === false) {

                    $villaFacilitiesDesc[str_replace('_description', '', $meta->meta_key)] = $meta->meta_value;


                }

            }

            if (is_array($villaFacilities) && is_array($villaFacilitiesDesc) && count($villaFacilities) && count($villaFacilitiesDesc)) {

                foreach ($villaFacilities as $key => $val) {
                    $cekk = \DB::connection('new')
                        ->table('property_metas')
                        ->where('name', $val)
                        ->where('property_id', $post->translate->trid)
                        ->first();
                    $vals = 1;
                    if (isset($villaFacilitiesDesc[$key])) {
                        $vals = $villaFacilitiesDesc[$key];
                    }
                    if (!count($cekk)) {
                        \DB::connection('new')
                            ->table('property_metas')
                            ->insert([
                                'name' => $val,
                                'property_id' => $post->translate->trid,
                                'value' => $vals,
                                'type' => 'facility'
                            ]);
                    }
                }
            }

            if ($meta->meta_key == 'bedroom') {

                $villaBedroom = $meta->meta_value;
            }

            if ($meta->meta_key == 'bathroom') {

                $villaBathroom = $meta->meta_value;
            }

            if ($meta->meta_key == 'currency') {
                $villaCurrency = $meta->meta_value;
                if ($villaCurrency == '12677') {
                    $villaCurrency = 'EUR';
                }
                if ($villaCurrency == '12678') {
                    $villaCurrency = 'IDR';
                }
                if ($villaCurrency == '12621') {
                    $villaCurrency = 'USD';
                }
            }


        }


        if (isset($villaImages)) {
            if (is_array($villaImages)) {
                foreach ($villaImages as $imgID) {
                    if (isset($pathUrl[$imgID])) {

//                        $ch = curl_init($pathUrl[$imgID]);
//
//                        curl_setopt($ch, CURLOPT_NOBODY, true);
//                        curl_exec($ch);
//                        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//                        curl_close($ch);
//
//                        if ($retcode == 200) {
//                            $remoteImages = file_get_contents($pathUrl[$imgID]);
//                            $img = \Image::make($remoteImages);
//
//                            $big = $img->resize(1000, null, function ($constraint) {
//                                $constraint->aspectRatio();
//                                $constraint->upsize();
//                            });
//                            $small = $img->resize(400, null, function ($constraint) {
//                                $constraint->aspectRatio();
//                                $constraint->upsize();
//                            });
//
//                            $big->save($path[$imgID]);
//                            $small->save($pathThumb[$imgID]);

                        \DB::connection('new')
                            ->table('attachments')
                            ->insert([
                                'attachable_id' => $post->translate->trid,
                                'attachable_type' => 'App\Property',
                                'file' => $path[$imgID],
                                'thumb' => $path[$imgID],
                                'type' => 'img'
                            ]);
//                        }
                    }


                }

            }
        }

        if (isset($villaDistanceMarket) && isset($villaDistanceMarketType)) {
            $chekz = \DB::connection('new')
                ->table('property_metas')
                ->where('property_id', $post->translate->trid)
                ->where('name', 'market')
                ->first();

            if (!count($chekz)) {
                \DB::connection('new')
                    ->table('property_metas')
                    ->insert([
                        'property_id' => $post->translate->trid,
                        'name' => 'market',
                        'value' => $villaDistanceMarket . ' ' . $villaDistanceMarketType,
                        'type' => 'distance',
                        'status' => 1
                    ]);
            }
        }


        if (isset($villaDistanceAirport) && isset($villaDistanceAirportType)) {
            $chekz = \DB::connection('new')
                ->table('property_metas')
                ->where('property_id', $post->translate->trid)
                ->where('name', 'airport')
                ->first();


            if (!count($chekz)) {
                \DB::connection('new')
                    ->table('property_metas')
                    ->insert([
                        'property_id' => $post->translate->trid,
                        'name' => 'airport',
                        'value' => $villaDistanceAirport . ' ' . $villaDistanceAirportType,
                        'type' => 'distance',
                        'status' => 1
                    ]);
            }

        }


        if (isset($villaDistanceBeach) && isset($villaDistanceBeachType)) {
            $chekz = \DB::connection('new')
                ->table('property_metas')
                ->where('property_id', $post->translate->trid)
                ->where('name', 'beach ')
                ->first();

            if (!count($chekz)) {
                \DB::connection('new')
                    ->table('property_metas')
                    ->insert([
                        'property_id' => $post->translate->trid,
                        'name' => 'beach',
                        'value' => $villaDistanceBeach . ' ' . $villaDistanceBeachType,
                        'type' => 'distance',
                        'status' => 1
                    ]);
            }
        }

        \DB::connection('new')
            ->table('property_locales')
            ->insert([
                'property_id' => $post->translate->trid,
                'title' => $title,
                'content' => $content,
                'slug' => $slug,
                'locale' => $post->translate->language_code,
            ]);

        $checker = \DB::connection('new')
            ->table('properties')
            ->where('id', $post->translate->trid)
            ->get();


        if (count($checker) == 0) {
            \DB::connection('new')
                ->table('properties')
                ->insert([
                    'id' => $post->translate->trid,
                    'price' => $villaPrice,
                    'type' => $villaStatus,
                    'building_size' => $villaBuildingSize,
                    'land_size' => $villaLandSize,
                    'bedroom' => $villaBedroom,
                    'bathroom' => $villaBathroom,
                    'code' => $villaCode,
                    'status' => $villaMetaStatus,
                    'year' => $villBuild,
                    'lease_year' => $villaEndYear,
                    'city' => implode(',', $villalocation),
                    'view_north' => $villaViewsNorth,
                    'view_east' => $villaViewsEast,
                    'view_south' => $villaViewsSouth,
                    'view_west' => $villaViewsWest,
                    'is_price_request' => $villaPriceOnRequest,
                    'is_exclusive' => $villaIsExclusive,
                    'owner_name' => $villaOwnerName,
                    'owner_phone' => $villaOwnerPhone,
                    'agent_commission' => $villaOwnerCommision,
                    'sell_reason' => $villaWhySell,
                    'lease_period' => $villaPeriode,
                    'currency' => $villaCurrency,
                    'sell_in_furnish' => $villaSellInFurnish,
                    'agent_inspector' => $villaInspectedBy,
                    'agent_contact' => $villaContactForViewing,
                    'other_agent' => $villaOtherListing,
                    'user_id' => $post->Users->ID
                ]);


            if (isset($villaType)) {
                if (is_array($villaType)) {
                    $villaType = array_unique($villaType);
                    foreach ($villaType as $tag) {

                        if ($tag == 'Investment until $ 500000') {
                            $tag = 3;
                        }

                        if ($tag == 'Beachfront Property') {
                            $tag = 1;
                        }

                        if ($tag == 'Home and retirement') {
                            $tag = 2;
                        }

                        if ($tag == 'Investment more than $ 500000') {
                            $tag = 4;
                        }

                        if ($tag == 'Hot Deals') {
                            $tag = 5;
                        }


                        \DB::connection('new')
                            ->table('tagables')
                            ->insert([
                                'tag_id' => $tag,
                                'tagable_id' => $post->translate->trid,
                                'tagable_type' => 'App\Property'
                            ]);

                    }
                }
            }

        }

    }


});


Route::group(['middleware' => ['web']], function () {
    //
});
