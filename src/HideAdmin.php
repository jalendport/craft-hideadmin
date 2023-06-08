<?php
/**
 * Hide Admin plugin for Craft CMS 3.x
 *
 * Hide admin accounts from non-admin accounts
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\hideadmin;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\controllers\UsersController;
use craft\elements\User;
use craft\events\RegisterElementSourcesEvent;
use craft\events\RegisterUserActionsEvent;

use yii\base\Event;
use yii\web\ForbiddenHttpException;

/**
 * Class HideAdmin
 *
 * @author    Jalen Davenport
 * @package   HideAdmin
 * @since     1.0.0
 *
 */
class HideAdmin extends Plugin
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        Event::on(
            User::class,
            Element::EVENT_REGISTER_SOURCES,
            function(RegisterElementSourcesEvent $event) {
                $currentUser = Craft::$app->getUser()->getIdentity();

                if (!$currentUser->admin) {
                    // Reset sources
                    $event->sources = [];

                    $groups = Craft::$app->getUserGroups()->getAllGroups();

                    if (!empty($groups)) {
                        $event->sources[] = ['heading' => Craft::t('app', 'Groups')];

                        foreach ($groups as $group) {
                            $event->sources[] = [
                                'key' => 'group:'.$group->uid,
                                'label' => Craft::t('site', $group->name),
                                'criteria' => ['groupId' => $group->id],
                                'hasThumbs' => true
                            ];
                        }
                    }
                }
            }
        );

        Event::on(
            UsersController::class,
            UsersController::EVENT_REGISTER_USER_ACTIONS,
            function(RegisterUserActionsEvent $event) {
                $currentUser = Craft::$app->getUser()->getIdentity();

                if (!$currentUser->admin) {
                    $user = $event->user;

                    // If request involves an admin throw exception
                    if($user->admin) {
                        throw new ForbiddenHttpException("Your account doesn't have permission to perform this action.");
                    }
                }
            }
        );
    }
}
