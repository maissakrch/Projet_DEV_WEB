<?php
require_once '../vendor/autoload.php';

use App\Page;

$page = new Page();
$user = $page->session->get('user'); 
$page->session->add('user', $user);

if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];
    
    if ($type === 'client' || $type === 'intervenant') {
        $userInfo = ($type === 'client') ? $page->getUserByID($id) : $page->getUserByID($id);

        if ($userInfo) {
            var_dump($userInfo);
            echo $page->render('viewmore.html.twig', [
                'type' => $type,
                'userInfo' => $userInfo[0] // Accessing the first element of the userInfo array
            ]);
        } else {
            echo "Utilisateur introuvable.";
        }
    } else if ($type === 'intervention'){
        $interventionInfo = ($type=='intervention')?$page->getInterventionsByID($id)  : false ;

        var_dump($interventionInfo);
        echo $page->render('viewmore.html.twig', [
            'type'=>$type,
            'id'=>$id,
            'interventionInfo'=>$interventionInfo
        ]);
    } else if ($type === 'standardiste'){
        $userInfo = ($type=='standardiste')?$page->getUserByID($id)  : false ;

        var_dump($userInfo);
        echo $page->render('viewmore.html.twig', [
            'type'=>$type,
            'userInfo'=>$userInfo[0]
        ]);
    } 
} else {
    echo "Erreur: Type ou ID manquant.";
}
