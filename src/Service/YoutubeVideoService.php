<?php


namespace App\Service;


use App\Entity\YoutubeVideo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

class YoutubeVideoService
{
    private $em;

    private $params;

    private $videoData;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->em = $entityManager;

        $this->params = $params;
    }

    public function setEmptyDataFromFormRequest(Request $request):void
    {
        $youtube_form = $request->request->get('post')['youtube'];

        $request_post = $request->request->get('post');
        $youtube_arr = $request_post['youtube'];

        $youtube_arr['active'] = true;
        if(!empty($youtube_form['videoId'])){
            $youtube_id = $this->getVideoId($youtube_form['videoId']);
            if($this->checkActiveVideo($youtube_id)){
                $youtube_arr = [
                    'videoId' => $youtube_id,
                    'name' => !empty($youtube_arr['name']) ? $youtube_arr['name'] : $this->getName($youtube_id),
                    'description' => !empty($youtube_arr['description']) ? $youtube_arr['description'] : $this->getDescription($youtube_id),
                    'previewImage' => !empty($youtube_arr['previewImage']) ? $youtube_arr['previewImage'] : $this->getPreviewImg($youtube_id),
                    'active' => true
                ];
            } else {
                $youtube_arr['active'] = false;
            }
        }
        $request_post['youtube'] = $youtube_arr;
        $request->request->set('post', $request_post);
    }

    public function removeVideo($id){
        $youtube = $this->em->getRepository(YoutubeVideo::class)->find($id);
        $this->em->remove($youtube);
        $this->em->flush();
    }

    public function getVideoId(string $youtube): string
    {
        $parts = parse_url($youtube);

        //if format url https://www.youtube.com/any/path?v=id
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!isset($query['v'])) {
                throw new \Exception('Error: invalid youtube url');
            }
            return $query['v'];
        }

        //if format https://www.youtube.com/any/path/id
        if (isset($parts['host'])) {
            $path = explode('/', $parts['path']);
            $id = $path[count($path) - 1];

            return $id;
        } else {
            //if set id
            return $parts['path'];
        }
    }

    public function createByVideoId(string $id, $params): YoutubeVideo
    {
        if ($this->checkActiveVideo($id)) {
            $youtube = $this->em->getRepository(YoutubeVideo::class)->findOneBy(['videoId' => $id]);
            if (empty($youtube)) {
                $youtube = new YoutubeVideo();
            }
            $youtube->setVideoId($id);
            $youtube->setName(isset($params['name']) ? $params['name'] : $this->getName($id));
            $youtube->setDescription(isset($params['description']) ? $params['description'] : $this->getDescription($id));
            $youtube->setPreviewImage(isset($params['previewImage']) ? $params['previewImage'] : $this->getPreviewImg($id));
            $this->em->persist($youtube);
            $this->em->flush();
            return $youtube;
        } else {
            throw new \Exception('Error: Video is not active');
        }
    }

    public function getName(string $id): string
    {
        return $this->getVideoData($id)['name'];
    }

    public function getPreviewImg(string $id): string
    {
        return $this->getVideoData($id)['previewImage'];
    }

    public function getDescription(string $id): string
    {
        return $this->getVideoData($id)['description'];
    }

    public function checkActiveVideo(string $id): bool
    {
        $videoData = $this->getVideoData($id);

        if (empty($videoData)) {
            return false;
        }

        return true;
    }

    private function getVideoData($id): array
    {
        if (isset($this->videoData['videoId']) && $this->videoData['videoId'] == $id) {
            return $this->videoData;
        }

        $api_key = $this->params->get('youtube.apiKey');
        $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $id . '&key=' . $api_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $res = json_decode(curl_exec($ch), true);

        if (!isset($res['items']) || count($res['items']) == 0) {
            return [];
        }

        $this->videoData = [
            'videoId' => $res['items'][0]['id'],
            'name' => $res['items'][0]['snippet']['title'],
            'description' => $res['items'][0]['snippet']['description'],
            'previewImage' => $res['items'][0]['snippet']['thumbnails']['maxres']['url']
        ];

        return $this->videoData;
    }
}