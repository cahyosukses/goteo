<?php
/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Controller;

use Symfony\Component\HttpFoundation\Request;

use Goteo\Application\Session;
use Goteo\Application\Config;
use Goteo\Application\View;
use Goteo\Model\Project;
use Goteo\Model\User;
use Goteo\Model\User\Interest;

class DashboardController extends \Goteo\Core\Controller {

    public function __construct() {
        // changing to a responsive theme here
        View::setTheme('responsive');
    }

    public function activityAction(Request $request) {
        $user = Session::getUser();

        // mis proyectos
        $projects = Project::ofmine($user->id);
        // proyectos que cofinancio
        $invested = User::invested($user->id, false);
        //proyectos que coinciden con mis intereses
        $favourite = Project::favouriteCategories($user->id, 0, 3);
        if($favourite) {
            $total_fav = Project::favouriteCategories($user->id, 0, 0, true);
        } else {
            $favourite = Project::published('popular', null, 0, 3);
            $total_fav = Project::published('popular', null, 0, 0, true);
        }


        $interests = Interest::getAll();

        return $this->viewResponse('dashboard/activity', [
            'projects' => $projects,
            'invested' => $invested,
            'interests' => $interests,
            'user_interests' => $user->interests,
            'favourite' => $favourite,
            'favourite_total' => $total_fav
        ]);
    }

    /**
     * Virtual wallet
     */
    public function walletAction(Request $request)
    {
        if(!Config::get('payments.pool.active')) {
            throw new \RuntimeException("Pool payment is not active!");
        }

        $user = Session::getUser();
        $pool = $user->getPool();
        $interests = Interest::getAll();

        //proyectos que coinciden con mis intereses
        $projects_suggestion = Project::favouriteCategories($user->id, 0, 6);

        if($projects_suggestion) {
            $total_fav = Project::favouriteCategories($user->id, 0, 0, true);
        } else {
            $projects_suggestion = Project::published('popular', null, 0, 6);
            $total_fav = Project::published('popular', null, 0, 0, true);
        }

        return $this->viewResponse('dashboard/wallet', [
            'pool' => $pool,
            'projects_suggestion' => $projects_suggestion,
            'projects_suggestion_total' => $total_fav,
            'user_interests' => $user->interests,
            'interests' => $interests,
            'popular_projects' => $popular_projects,
            'section' => 'pool' ]
        );

    }

}
