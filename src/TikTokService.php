<?php
namespace TikTokDer;

class TikTokService
{
    public $error = null;

    protected $tries = 0;

    protected $videoPath;

    protected $baseUrl;

    public function __construct($videoPath, $baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->videoPath = $videoPath;
        $this->createFolderForVideos();
    }

    /**
     * @param $link
     * @return array|bool
     */
    public function getVideoDataByLink($link) {

        if(strpos($link, 'tiktok.com') === false) {
            $this->error = 'wrong_link';
            return false;
        }

        return $this->getVideoWithoutWatermark($link);
    }


    public function getVideoWithoutWatermark( $link ) {
        $agent= 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:70.0) Gecko/20100101 Firefox/70.0';

        $host = explode('//', $link)[1];
        $host = explode('/', $host)[0];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $link);
        $html=curl_exec($ch);

        $json = explode('__NEXT_DATA__" type="application/json" crossorigin="anonymous">', $html)[1];
        $json = explode('</script>', $json)[0];
        $json = json_decode($json, true);

        $realvideoUrl = explode("?", curl_getinfo($ch, CURLINFO_EFFECTIVE_URL))[0];
        if(is_array($json) && count($json) > 0) {
            $videoId = $json['props']['pageProps']['itemInfo']['itemStruct']['id'];
            $previewImg = $json['props']['pageProps']['itemInfo']['itemStruct']['video']['cover'];
            $description = $json['props']['pageProps']['itemInfo']['itemStruct']['desc'];


            $avatar = $json['props']['pageProps']['itemInfo']['itemStruct']['author']['avatarThumb'];
            $nickname = '@' . $json['props']['pageProps']['itemInfo']['itemStruct']['author']['uniqueId'];
            $name = $json['props']['pageProps']['itemInfo']['itemStruct']['author']['nickname'];

            $tiktokViews = $json['props']['pageProps']['itemInfo']['itemStruct']['stats']['playCount'];
            $commentCount = $json['props']['pageProps']['itemInfo']['itemStruct']['stats']['commentCount'];
            $likeCount = $json['props']['pageProps']['itemInfo']['itemStruct']['stats']['diggCount'];
            $shareCount = $json['props']['pageProps']['itemInfo']['itemStruct']['stats']['shareCount'];

            $withoutWm = $source = $json['props']['pageProps']['videoData']['itemInfos']['video']['urls'][0];
            $words = explode(' ', $description);
            $hashtags = '';
            if(is_array($words) && count($words) > 0) {
                $hashtags = [];
                $description .= ' ';
                foreach ($words as $word) {
                    if(strpos($word, '#') === 0) {
                        $description = str_ireplace($word . ' ', '', $description);
                        $hashtags[] = $word;
                    }
                }
                $description = trim($description);
                $hashtags = implode(' ', $hashtags);
            }
        }

        return [
            'source' => $source,
            'preview_img' => $previewImg,
            'description' => $description,
            'hashtags' => $hashtags,
            'video_id' => $videoId,
            'tiktok_views' => $tiktokViews,
            'author' => [
                'nickname' => $nickname,
                'name' => $name,
                'avatar' => $avatar
            ],
            'video_source' => $source,
            'video_source_no_wm' => $source,
            'hd' => $hd,
            'view_count' => $this->numberFormat($tiktokViews),
            'comment_count' => $this->numberFormat($commentCount),
            'share_count' => $this->numberFormat($shareCount),
            'like_count' => $this->numberFormat($likeCount),
        ];
    }

    protected function createFolderForVideos() {
        $today = date("Ym" );
        $this->videoPath = $this->videoPath . '/' . $today;
        if (!file_exists($this->videoPath)) {
            mkdir($this->videoPath, 0777, true);
        }
    }

    /**
     * Makes cool round for numbers
     * Example: 1800 -> 1.8K
     * @param $n
     * @return bool|string
     */
    public static function numberFormat($n) {
        $n = (0+str_replace(",","",$n));

        if(!is_numeric($n)) return false;

        else if($n>1000000000) return round(($n/1000000000),1).'B';
        else if($n>1000000) return round(($n/1000000),1).'M';
        else if($n>1000) return round(($n/1000),1).'K';

        return number_format($n);
    }
}
