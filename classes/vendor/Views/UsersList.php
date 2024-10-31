<?php
/**
 * Plugin WPS security
 *
 * @package   WPS
 * @author    wp-security.com
 * @copyright Copyright © 2021
 */

declare( strict_types = 1 );

namespace WPSEC\Views;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use WPSEC\Controllers\User;
use WPSEC\Helpers\WP_Helper;

if ( ! class_exists( 'WPSEC\Views\UsersList' ) ) {
    /**
     * Responsible for users listing table
     *
     * @since 1.0.0
     */
    class UsersList {

        /**
         * The users table locked custom column name
         *
         * @since 1.0.0
         *
         * @var string
         */
        private static $lockedColumnName = 'locked-out';

        /**
         * The users table logged custom column name
         *
         * @since 1.0.0
         *
         * @var string
         */
        private static $loggedColumnName = 'logged-user';

        /**
         * The users table 2fa status
         *
         * @since 1.0.0
         *
         * @var string
         */
        private static $securedWpStatus = 'secured-wp-status';

        /**
         * Bulk action change to lock status name
         *
         * @var string
         *
         * @since 1.7
         */
        private static $changeLockedBulkActionName = 'change-to-locked';

        /**
         * Bulk action change to unlock status name
         *
         * @var string
         *
         * @since 1.7
         */
        private static $changeUnLockedBulkActionName = 'change-to-unlocked';

        /**
         * Bulk action exclude user from 2FA
         *
         * @var string
         *
         * @since 1.7
         */
        private static $changeExcludeTwoFaBulkActionName = 'exclude-2fa';

        /**
         * Bulk action include user to 2FA
         *
         * @var string
         *
         * @since 1.7
         */
        private static $changeIncludeTwoFaBulkActionName = 'include-2fa';

        /**
         * Inits the class, adds all the hooks
         *
         * @since 1.0.0
         *
         * @return void
         */
        public static function init() {

            /**
             * Is module active
             */
            if ( (bool) \WPSEC\Controllers\Modules\Login_Attempts::get_global_settings_value() ) {

                /**
                 * Show logged in column
                 */
                \add_filter( 'manage_users_columns', [ __CLASS__, 'addUsersLockedColumn' ] );

                /**
                 * External object cache means there is no way to use DB for sorting that column
                 * There is an add_filter('all' ... hook which could be used to store data in
                 * lets say UserMeta - but that is a bit extreme ...
                 */
                if ( ! \wp_using_ext_object_cache() ) {
                    \add_filter( 'manage_users_sortable_columns', [ __CLASS__, 'addUsersLockedColumnSort' ] );
                    /**
                     * Sort option for locked users
                     */
                    \add_action( 'pre_user_query', [ __CLASS__, 'sortLockedUsers' ], 10, 1 );
                }

                \add_filter( 'manage_users_custom_column', [ __CLASS__, 'lockedUserColumnData' ], 10, 3 );

                if ( WP_Helper::is_multisite() ) {
                    \add_filter( 'wpmu_users_columns', [ __CLASS__, 'addUsersLockedColumn' ] );
                }
            }

            // wpmu_users_columns
            // manage_users - network_sortable_columns

            /**
             * Show logged in column
             */
            \add_filter( 'manage_users_columns', [ __CLASS__, 'addUsersLoggedColumn' ] );
            \add_filter( 'manage_users_sortable_columns', [ __CLASS__, 'addUsersLoggedColumn' ] );
            \add_filter( 'manage_users_custom_column', [ __CLASS__, 'loggedUserColumnData' ], 10, 3 );

            if ( WP_Helper::is_multisite() ) {
                \add_filter( 'wpmu_users_columns', [ __CLASS__, 'addUsersLoggedColumn' ] );
                \add_filter( 'manage_users-network_sortable_columns', [ __CLASS__, 'addUsersLoggedColumn' ] );
            }

            /**
             * Show Secured Wp status column
             */
            \add_filter( 'manage_users_columns', [ __CLASS__, 'addUsersStatusColumn' ] );
            \add_filter( 'manage_users_custom_column', [ __CLASS__, 'userStatusColumnData' ], 10, 3 );

            if ( WP_Helper::is_multisite() ) {
                \add_filter( 'wpmu_users_columns', [ __CLASS__, 'addUsersStatusColumn' ] );
            }

            /**
             * Sort option for logged in users
             */
            \add_action( 'pre_user_query', [ __CLASS__, 'sortLoggedUsers' ], 10, 1 );

            \add_filter( 'bulk_actions-users', [ __CLASS__, 'addUsersBulkAction' ] );
            \add_filter( 'handle_bulk_actions-users', [ __CLASS__, 'handleUsersBulkAction' ], 10, 3 );
            \add_action( 'admin_notices', [ __CLASS__, 'bulkLockUserNotices' ] );

            /**
             * Fiter users by roles
             */
            \add_action( 'restrict_manage_users', [ __CLASS__, 'filterByRole' ] );
            // adds javascript in the footer to keep 2 users roles dropdowns in sync.
            \add_action( 'admin_footer', [ __CLASS__, 'filterByRoleJS' ] );
        }

        /**
         * Sets the user locked column
         *
         * @since 1.0.0
         *
         * @param array $columns - array with all user columns.
         *
         * @return array
         */
        public static function addUsersLockedColumn( array $columns ): array {
            $columns[ self::$lockedColumnName ] = __( 'Locked status', 'secured-wp' );
            return $columns;
        }

        /**
         * Sets the user locked column
         *
         * @since 1.5
         *
         * @param array $columns - array with all user columns.
         *
         * @return array
         */
        public static function addUsersLockedColumnSort( array $columns ): array {
            $columns[ self::$lockedColumnName ] = self::$lockedColumnName;
            return $columns;
        }

        /**
         * Sets the locked user column data
         *
         * @since 1.0.0
         *
         * @param string $value - current value.
         * @param string $columnName - name of the column.
         * @param int    $userId - processed user.
         *
         * @return mixed
         */
        public static function lockedUserColumnData( string $value, string $columnName, int $userId ) {

            switch ( $columnName ) {
                case self::$lockedColumnName:
                    return User::is_locked( $userId ) ? \__( 'Locked', 'secured-wp' ) : \__( 'Not Locked', 'secured-wp' );
                default:
                    break;
            }

            return $value;
        }

        /**
         * Sets the user logged column
         *
         * @since 1.0.0
         *
         * @param array $columns - array with all user columns.
         *
         * @return array
         */
        public static function addUsersStatusColumn( array $columns ): array {
            $columns[ self::$securedWpStatus ] = __( 'Secured WP Status', 'secured-wp' );
            return $columns;
        }

        /**
         * Sets the user Secured WP status column data
         *
         * @since 1.0.0
         *
         * @param string $value - current value.
         * @param string $columnName - name of the column.
         * @param int    $userId - processed user.
         *
         * @return mixed
         */
        public static function userStatusColumnData( string $value, string $columnName, int $userId ) {

            switch ( $columnName ) {
                case self::$securedWpStatus:
                    return User::get_status( $userId );
                default:
                    break;
            }

            return $value;
        }

        /**
         * Sets the user Secured WP column
         *
         * @since 1.0.0
         *
         * @param array $columns - array with all user columns.
         *
         * @return array
         */
        public static function addUsersLoggedColumn( array $columns ): array {
            $columns[ self::$loggedColumnName ] = __( 'Logged in', 'secured-wp' );
            return $columns;
        }

        /**
         * Sets the logged in user column data
         *
         * @since 1.0.0
         *
         * @param string $value - current value.
         * @param string $columnName - name of the column.
         * @param int    $userId - processed user.
         *
         * @return mixed
         */
        public static function loggedUserColumnData( string $value, string $columnName, int $userId ) {

            switch ( $columnName ) {
                case self::$loggedColumnName:
                    return User::is_logged( $userId ) ? __( 'Logged', 'secured-wp' ) : __( 'Not Logged', 'secured-wp' );
                default:
                    break;
            }

            return $value;
        }

        /**
         * Sets the proper order based on the locked user status
         *
         * @since 1.0.0
         *
         * @param \WP_User_Query $wPUserQuery - all users query.
         *
         * @return void
         */
        public static function sortLockedUsers( &$wPUserQuery ) {
            global $wpdb;

            if ( isset( $wPUserQuery->query_vars['orderby'] )
            && ( self::$lockedColumnName === $wPUserQuery->query_vars['orderby'] )
            ) {
                $wPUserQuery->query_orderby = " ORDER BY (SELECT wpo.option_value FROM {$wpdb->options} as wpo
            WHERE wpo.option_value={$wpdb->users}.user_login
            AND wpo.option_name like '_transient_attempted_login_%')"
                . ( ( 'ASC' === $wPUserQuery->query_vars['order'] ) ? 'ASC ' : 'DESC' );
            }
        }

        /**
         * Sorts the logged users
         *
         * @since 1.0.0
         *
         * @param \WP_User_Query $wPUserQuery - all users query.
         *
         * @return void
         */
        public static function sortLoggedUsers( &$wPUserQuery ) {
            global $wpdb;

            if ( isset( $wPUserQuery->query_vars['orderby'] )
            && ( __( 'Logged in', 'secured-wp' ) === $wPUserQuery->query_vars['orderby'] )
            ) {
                $wPUserQuery->query_orderby = " ORDER BY (SELECT wpo.meta_key FROM {$wpdb->usermeta} as wpo
            WHERE wpo.user_id={$wpdb->users}.ID
            AND wpo.meta_key = 'session_tokens')"
                . ( ( 'ASC' === $wPUserQuery->query_vars['order'] ) ? 'ASC ' : 'DESC' );
            }
        }

        /**
         * Add bulk actions to Users table
         *
         * @since 1.0.0
         *
         * @param array $bulkActions - array with bulk actions.
         *
         * @return array
         */
        public static function addUsersBulkAction( array $bulkActions ): array {
            if ( (bool) \WPSEC\Controllers\Modules\Login_Attempts::get_global_settings_value() ) {
                $bulkActions[ self::$changeLockedBulkActionName ]   = __( 'Lock Users', 'secured-wp' );
                $bulkActions[ self::$changeUnLockedBulkActionName ] = __( 'Unlock Users', 'secured-wp' );
            }
            $bulkActions[ self::$changeExcludeTwoFaBulkActionName ] = __( 'Exclude from 2FA', 'secured-wp' );
            $bulkActions[ self::$changeIncludeTwoFaBulkActionName ] = __( 'Include in 2FA', 'secured-wp' );

            return $bulkActions;
        }

        /**
         * Handles users bulk actions - process redirect link
         *
         * @since 1.0.0
         *
         * @param string $redirectTo - where to redirect URL.
         * @param string $doAction - action name.
         * @param array  $userIds - user ids list.
         *
         * @return string
         */
        public static function handleUsersBulkAction( string $redirectTo, string $doAction, array $userIds ): string {
            if ( \current_user_can( 'administrator' ) ) {
                if ( self::$changeLockedBulkActionName !== $doAction &&
                self::$changeUnLockedBulkActionName !== $doAction &&
                self::$changeExcludeTwoFaBulkActionName !== $doAction &&
                self::$changeIncludeTwoFaBulkActionName !== $doAction
                ) {
                    return $redirectTo;
                }

                $redirectTo = \remove_query_arg(
                    [
                        self::$changeLockedBulkActionName,
                        self::$changeUnLockedBulkActionName,
                        self::$changeExcludeTwoFaBulkActionName,
                        self::$changeIncludeTwoFaBulkActionName,
                    ],
                    $redirectTo
                );

                if ( self::$changeLockedBulkActionName === $doAction ) {
                    foreach ( $userIds as $userId ) {
                        User::lock_user( $userId, true );
                    }
                }

                if ( self::$changeUnLockedBulkActionName === $doAction ) {
                    foreach ( $userIds as $userId ) {
                        User::unlock_user( $userId );
                    }
                }

                if ( self::$changeExcludeTwoFaBulkActionName === $doAction ) {
                    foreach ( $userIds as $userId ) {
                        User::exclude_two_fa( $userId );
                    }
                }

                if ( self::$changeIncludeTwoFaBulkActionName === $doAction ) {
                    foreach ( $userIds as $userId ) {
                        User::include_two_fa( $userId );
                    }
                }
            }
            return $redirectTo;
        }

        /**
         * Notice admin about the bulk action results
         *
         * @since 1.0.0
         *
         * @return void
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public static function bulkLockUserNotices() {

            if ( ! empty( $_REQUEST[ self::$changeLockedBulkActionName ] ) ) {
                $updated = intval( $_REQUEST[ self::$changeLockedBulkActionName ] );
                printf(
                    '<div id="message" class="updated">' .
                    \esc_html(
                        /* translators: %s: Number of users */
                        \_n(
                            'Locked %s user.',
                            'Locked %s users.',
                            $updated,
                            'secured-wp'
                        )
                    ) . '</div>',
                    \esc_html( $updated )
                );
            }

            if ( ! empty( $_REQUEST[ self::$changeUnLockedBulkActionName ] ) ) {
                $updated = intval( $_REQUEST[ self::$changeUnLockedBulkActionName ] );
                printf(
                    '<div id="message" class="updated">' .
                    \esc_html(/* translators: %s: Number of users */
                        _n(
                            'Unlocked %s user.',
                            'Unlocked %s users.',
                            $updated,
                            'secured-wp'
                        )
                    ) . ' </div> ',
                    \esc_html( $updated )
                );
            }
        }

        /**
         * Creates dropdown with all the roles in the WP by which it could be filtered
         *
         * @since 1.0.0
         *
         * @param string $which - which role to process.
         *
         * @return void
         *
         * @SuppressWarnings(PHPMD.Superglobals)
         */
        public static function filterByRole( string $which ) {
            $id       = 'bottom' === $which ? 'filter_role2' : 'filter_role';
            $buttonId = 'bottom' === $which ? 'filterit2' : 'filterit';

            ?>
        <label class="screen-reader-text" for="<?php echo \esc_attr( $id ); ?>"><?php \esc_html_e( 'Filter Role', 'secured-wp' ); ?></label>
        <select name="role" style="float:none;margin-left:10px" id="<?php echo \esc_attr( $id ); ?>">
            <option value=""><?php \esc_html_e( 'Filter Role', 'secured-wp' ); ?></option>
            <?php
            // generate options.
            \wp_dropdown_roles(
                ( ( isset( $_GET['role'] ) && ! empty( $_GET['role'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['role'] ) ) : null )
            );
            ?>
        </select>
            <?php
            \submit_button( __( 'Filter' ), null, $buttonId, false );
        }

        /**
         * Add JS in the admin footer to keep both filter by role dropdowns in sync
         *
         * @since 1.0.0
         *
         * @return void
         */
        public static function filterByRoleJS() {
            ?>
            <script>
                var userRolesSelects = jQuery( "#filter_role, #filter_role2" );
                userRolesSelects.change(function(e) {
                    userRolesSelects.val( this.value ); // "this" is the changed one
                });
            </script>
            <?php
        }
    }
}
