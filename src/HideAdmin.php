<?php
/**
 * Hide Admin plugin for Craft CMS 4.x
 *
 * Hide admin accounts from non-admin accounts
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\hideadmin;

use craft\base\conditions\BaseCondition;
use craft\base\Element;
use craft\base\Plugin;
use craft\controllers\UsersController;
use craft\elements\conditions\users\AdminConditionRule;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\events\AuthorizationCheckEvent;
use craft\events\RegisterConditionRuleTypesEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\services\Elements;
use craft\services\ElementSources;
use craft\web\Controller;
use jalendport\hideadmin\services\HiddenUsers;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\web\NotFoundHttpException;

/**
 * @author    Jalen Davenport
 * @package   HideAdmin
 * @since     1.0.0
 *
 * @property-read HiddenUsers $hiddenUsers
 */
class HideAdmin extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var HideAdmin
     */
    public static HideAdmin $plugin;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function config(): array
    {
        return [
            'components' => [
                'hiddenUsers' => HiddenUsers::class,
            ],
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerUsersControllerGuard();
        $this->_registerAuthorizationChecks();
        $this->_registerElementSources();
        $this->_registerConditionRules();
    }

    // Private Methods
    // =========================================================================

    /**
     * Answers “not found” when a non-admin targets an admin user through any
     * users controller action — the edit screen, save, delete, and the rest.
     * Craft 4’s users controller loads its target by ID and only checks the
     * general “Edit users” permission, so this is what closes the direct-URL
     * hole.
     *
     * @return void
     */
    private function _registerUsersControllerGuard(): void
    {
        Event::on(
            UsersController::class,
            Controller::EVENT_BEFORE_ACTION,
            static function(ActionEvent $event): void {
                if (!self::$plugin->hiddenUsers->shouldFilter()) {
                    return;
                }

                $target = self::$plugin->hiddenUsers->getRequestedUser();
                $viewer = self::$plugin->hiddenUsers->getViewer();

                if ($target !== null && $viewer !== null && self::$plugin->hiddenUsers->isHidden($target, $viewer)) {
                    throw new NotFoundHttpException('User not found');
                }
            }
        );
    }

    /**
     * Denies non-admins from viewing, saving, or deleting admin users through
     * any path that authorizes elements via the elements service (element
     * slideouts, the generic elements controller) — defense in depth alongside
     * the users controller guard above.
     *
     * @return void
     */
    private function _registerAuthorizationChecks(): void
    {
        $deny = static function(AuthorizationCheckEvent $event): void {
            $target = $event->element;

            if ($target instanceof User && self::$plugin->hiddenUsers->isHidden($target, $event->user)) {
                $event->authorized = false;
            }
        };

        Event::on(Elements::class, Elements::EVENT_AUTHORIZE_VIEW, $deny);
        Event::on(Elements::class, Elements::EVENT_AUTHORIZE_SAVE, $deny);
        Event::on(Elements::class, Elements::EVENT_AUTHORIZE_DELETE, $deny);
    }

    /**
     * Hides admin users from the Users element index for non-admins. Scoped to
     * the index context so relation-field, modal, and settings source
     * resolution stay intact (see issue #9).
     *
     * @return void
     */
    private function _registerElementSources(): void
    {
        Event::on(
            User::class,
            Element::EVENT_REGISTER_SOURCES,
            static function(RegisterElementSourcesEvent $event): void {
                if ($event->context !== ElementSources::CONTEXT_INDEX) {
                    return;
                }

                if (!self::$plugin->hiddenUsers->shouldFilter()) {
                    return;
                }

                $event->sources = self::$plugin->hiddenUsers->filterSources($event->sources);
            }
        );
    }

    /**
     * Removes the “Admin” rule from the Users index filter builder for
     * non-admins, so they can’t re-reveal admin users through a filter.
     *
     * @return void
     */
    private function _registerConditionRules(): void
    {
        Event::on(
            UserCondition::class,
            BaseCondition::EVENT_REGISTER_CONDITION_RULE_TYPES,
            static function(RegisterConditionRuleTypesEvent $event): void {
                if (!self::$plugin->hiddenUsers->shouldFilter()) {
                    return;
                }

                $event->conditionRuleTypes = array_values(array_filter(
                    $event->conditionRuleTypes,
                    static fn($type): bool => (is_array($type) ? ($type['class'] ?? null) : $type) !== AdminConditionRule::class
                ));
            }
        );
    }
}
