<?php

namespace App\Service;

use App\Entity\Friendship;
use App\Entity\Game;
use App\Entity\Library;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use App\Repository\GameRepository;
use App\Repository\LibraryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class steamApi
{
    private $client;
    private $gameRepository;
    private $friendshipRepository;
    private $userRepository;
    private $libraryRepository;
    private $em;

    public function __construct(HttpClientInterface $client, LibraryRepository $libraryRepository, GameRepository $gameRepository, FriendshipRepository $friendshipRepository, UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->gameRepository  = $gameRepository;
        $this->libraryRepository = $libraryRepository;
        $this->friendshipRepository = $friendshipRepository;
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    public function fetchUserInfo(string $steamId): array
    {
        $response = $this->client->request(
            'GET',
            'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=8042AD2C22CFB15EE1A668BACFEB5D27&steamids='.$steamId);

        $content = $response->toArray();
        $content = $content["response"]["players"][0];

        // Set to "true" or "false" the "communityvisibiltystate" keyto match our db because the api returns 3 or 1
        if ($content["communityvisibilitystate"] === 3) {
            $content["communityvisibilitystate"] = true;
        }
        else {
            $content["communityvisibilitystate"] = false;
        }

        return $content;
    }

    public function updateUserInfo(User $user): User
    {
        $response = $this->client->request(
            'GET',
            'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=8042AD2C22CFB15EE1A668BACFEB5D27&steamids='.$user->getSteamId());

        $content = $response->toArray();
        $content = $content["response"]["players"][0];

        // If the Steam pseudo has changed
        if ($user->getSteamUsername() !== $content["personaname"]) {
            $user->setSteamUsername($content["personaname"]);
        }
        // If the Steam avatar has changed
        if ($user->getSteamAvatar() !== $content["avatarfull"]) {
            $user->setSteamAvatar($content["avatarfull"]);
        }


        // Set to "true" or "false" the "communityvisibiltystate" keyto match our db because the api returns 3 or 1
        if ($content["communityvisibilitystate"] === 3) {
            $content["communityvisibilitystate"] = true;
        }
        else {
            $content["communityvisibilitystate"] = false;
        }
        // If the Steam visibilty state has changed
        if ($user->getVisibilityState() !== $content["communityvisibilitystate"]) {
            $user->setVisibilityState($content["communityvisibilitystate"]);
        }

        $this->em->flush();
        
        return $user;
    }
    
    // TODO : creer des variables user et games pour simplifier les param??tres 
    public function fetchGamesInfo(string $steamId)
    {
        $response = $this->client->request(
            'GET',
            'http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=8042AD2C22CFB15EE1A668BACFEB5D27&steamid='.$steamId.'&include_appinfo=true&format=json');

        $content = $response->toArray();

        if (array_key_exists("games", $content["response"])) {
            $games = $content["response"]["games"];
        }
        else {
            return false;
        }
        

        foreach($games as $currentGame){
            if (!$this->gameRepository->findOneBy(['appid' => $currentGame['appid']])){

                $curentGamePictureUrl = 'http://media.steampowered.com/steamcommunity/public/images/apps/'.$currentGame['appid'].'/'.$currentGame['img_logo_url'].'.jpg';

                $newGame = new Game();
                $newGame
                ->setName($currentGame['name'])
                ->setAppid($currentGame['appid'])
                ->setPicture($curentGamePictureUrl);

                $this->em->persist($newGame);

                $newLibrary = new Library();

                $newLibrary
                ->setUser($this->userRepository->findOneBy(['steamId' => $steamId]))
                ->setGame($newGame);

                $this->em->persist($newLibrary);

                $this->em->flush();
            }

            if (!$this->libraryRepository->findOneByGameAndUser($this->gameRepository->findOneBy(['appid' => $currentGame['appid']]), $this->userRepository->findOneBy(['steamId' => $steamId]))) {
               
                $newLibrary = new Library();

                $newLibrary
                ->setUser($this->userRepository->findOneBy(['steamId' => $steamId]))
                ->setGame($this->gameRepository->findOneBy(['appid' => $currentGame['appid']]));

                $this->em->persist($newLibrary);

                $this->em->flush();
            }
            // else{
            //     return 'ok';
            // }
        }

        return true;
    }

    public function fetchFriendsInfo($steamId)
    {
        $response = $this->client->request(
            'GET',
            'https://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=8042AD2C22CFB15EE1A668BACFEB5D27&steamid='.$steamId.'&relationship=friend');

        $content = $response->toArray();

        if (array_key_exists("friends", $content["friendslist"])) {
            $friends = $content["friendslist"]["friends"];
        }
        else {
            return false;
        }
        

        // dd($friends);
        // dd($this->userRepository->findOneBy(['steamId' => $friends[1]['steamid'] ]));
        foreach($friends as $currentFriend){
            // dd($this->userRepository->findOneBy(['id' => $currentFriend['steamid'] ]));
            
            if ($this->userRepository->findOneBy(['steamId' => $currentFriend['steamid'] ]) && !$this->friendshipRepository->findOneByUserAndFriend($this->userRepository->findOneBy(['steamId' => $steamId]), $this->userRepository->findOneBy(['steamId' => $currentFriend['steamid']]))) {

                // dd('hiufhviudhe');
                $actualUser   = $this->userRepository->findOneBy(['steamId' => $steamId]);
                $hisNewFriend = $this->userRepository->findOneBy(['steamId' => $currentFriend['steamid']]);

                // dd($actualUser);
                // dd($hisNewFriend);

                $newFriendship = new Friendship();
                $newFriendship
                ->setFriend($actualUser)
                ->setUser($hisNewFriend);
                $this->em->persist($newFriendship);

                $newFriendshipReverse = new Friendship();
                $newFriendshipReverse
                ->setFriend($hisNewFriend)
                ->setUser($actualUser);
                $this->em->persist($newFriendshipReverse);

                // $hisNewFriend->addFriend($newFriendship);
                // $actualUser->addFriend($newFriendshipReverse);
                               
                $this->em->flush();
            }
        }

        return true;
    }
}