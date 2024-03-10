<?php

    require_once '../vendor/autoload.php';

    use App\Page;

    $page = new Page();
    $user = $page->session->get('user');
    $page->session->add('user',$user);

    if ($user) {
        $role = $user['role'];
        switch ($role) {
            case 'client':
                // Afficher la page d'accueil du client
                //header('Location: profile.php');
                break;
            case 'intervenant':
                // Afficher la page d'accueil de l'intervenant
                //header('Location: profile.php');
                break;
            case 'standardiste':
                $interventions=$page->getAllInterventions();
                echo $page->render('home_standardiste.html.twig',[
                    'user'=>$user,
                    'interventions'=>$interventions
                ]);
            case 'admin':
                $standardistes=$page->getAllStandardiste();
                $interventions = $page->getAllInterventions();
                $intervenants = $page->getAllIntervenent();
                $newCustomers = $page->getAllNewCustormers();
                $customers = $page->getAllCustomers();
                echo $page->render('home_admin.html.twig', 
                    ['newCustomers'=>$newCustomers,
                    'user'=>$user,
                    'standardistes'=>$standardistes,
                    'interventions'=>$interventions,
                    'intervenants'=>$intervenants,
                    'customers'=>$customers]
                );

            default:
                // Cas par défaut : afficher une page d'accueil générique
                //echo $page->render('home_generic.html.twig');
                break;
        }
    } else {
        // L'utilisateur n'est pas connecté, rediriger vers la page de connexion
        header('Location: index.php');
        exit(); // Arrêter l'exécution du script après la redirection
    }
    ?>

        
            