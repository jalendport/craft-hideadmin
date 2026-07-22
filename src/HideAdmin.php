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
use craft\elements\conditions\users\AdminConditionRule;
use craft\elements\conditions\users\UserCondition;
use craft\elements\db\ElementQuery;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\events\AuthorizationCheckEvent;
use craft\events\CancelableEvent;
use craft\events\RegisterConditionRuleTypesEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\services\Elements;
use craft\services\ElementSources;
use jalendport\hideadmin\services\HiddenUsers;
use yii\base\Event;

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

        $this->_registerUserQueryFilter();
        $this->_registerAuthorizationChecks();
        $this->_registerElementSources();
        $this->_registerConditionRules();
    }

    // Private Methods
    // =========================================================================

    /**
     * Excludes admin users from every user query made by a non-admin in the
     * control panel. This is the primary access control: Craft 4’s users
     * controller loads its target with `User::find()->id()->one()` and only
     * checks the general “Edit users” permission, so filtering the query is what
     * makes an admin’s edit screen return “not found” for a non-admin.
     *
     * `subQuery` is filtered (mirroring how core applies the `admin` param) with
     * an additive `andWhere`, so a condition-builder `admin` value can’t
     * override it. Front-end, GraphQL, console, and queue requests are left
     * untouched by [[HiddenUsers::shouldFilter()]].
     *
     * @return void
     */
    private function _registerUserQueryFilter(): void
    {
        Event::on(
            UserQuery::class,
            ElementQuery::EVENT_BEFORE_PREPARE,
            static function(CancelableEvent $event): void {
                if (!self::$plugin->hiddenUsers->shouldFilter()) {
                    return;
                }

                /** @var UserQuery $query */
                $query = $event->sender;
                $query->subQuery?->andWhere(['not', ['users.admin' => true]]);
            }
        );
    }

    /**
     * Denies non-admins from viewing, saving, or deleting admin users through
     * any path that authorizes elements via the elements service (element
     * slideouts, the generic elements controller) — defense in depth alongside
     * the query filter above.
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
