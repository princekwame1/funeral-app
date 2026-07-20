<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class Permissions
{
    // --- Dashboard ---
    public const DASHBOARD_VIEW = 'dashboard.view';

    // --- Donations ---
    public const DONATIONS_VIEW = 'donations.view';
    public const DONATIONS_CREATE = 'donations.create';
    public const DONATIONS_VERIFY = 'donations.verify';

    // --- SMS: general notifications ---
    public const SMS_NOTIFICATIONS_VIEW = 'sms.notifications.view';
    public const SMS_NOTIFICATIONS_SEND = 'sms.notifications.send';

    // --- SMS: funeral invitations ---
    public const SMS_INVITATIONS_VIEW = 'sms.invitations.view';
    public const SMS_INVITATIONS_SEND = 'sms.invitations.send';

    // --- SMS: post-funeral ---
    public const SMS_POST_VIEW = 'sms.post.view';
    public const SMS_POST_SEND = 'sms.post.send';

    // --- SMS: logs ---
    public const SMS_LOGS_VIEW = 'sms.logs.view';

    // --- SMS: contacts & groups ---
    public const CONTACTS_VIEW = 'contacts.view';
    public const CONTACTS_MANAGE = 'contacts.manage';
    public const CONTACTS_IMPORT = 'contacts.import';

    // --- Tenant admin (super only) ---
    public const BRANDING_VIEW = 'branding.view';
    public const BRANDING_EDIT = 'branding.edit';

    // --- Tenant team (users within own tenant) ---
    public const TEAM_VIEW = 'team.view';
    public const TEAM_CREATE = 'team.create';
    public const TEAM_DELETE = 'team.delete';

    // --- Events schedule ---
    public const EVENTS_VIEW = 'events.view';
    public const EVENTS_MANAGE = 'events.manage';

    // --- Platform (super only) ---
    public const PLATFORM_OVERVIEW = 'platform.overview';
    public const TENANTS_VIEW = 'tenants.view';
    public const TENANTS_CREATE = 'tenants.create';
    public const TENANTS_EDIT = 'tenants.edit';
    public const TENANTS_DELETE = 'tenants.delete';
    public const TENANTS_SWITCH = 'tenants.switch';
    public const USERS_VIEW = 'users.view';
    public const USERS_CREATE = 'users.create';
    public const USERS_DELETE = 'users.delete';
    public const USERS_IMPERSONATE = 'users.impersonate';
    public const ROLES_VIEW = 'roles.view';
    public const ROLES_EDIT = 'roles.edit';
    public const PLANS_MANAGE = 'plans.manage';
    public const WEBHOOKS_VIEW = 'webhooks.view';

    /**
     * Group permissions by module. Order matters for the roles table.
     */
    public const MODULES = [
        'Dashboard' => [
            self::DASHBOARD_VIEW,
        ],
        'Donations' => [
            self::DONATIONS_VIEW,
            self::DONATIONS_CREATE,
            self::DONATIONS_VERIFY,
        ],
        'SMS · General notifications' => [
            self::SMS_NOTIFICATIONS_VIEW,
            self::SMS_NOTIFICATIONS_SEND,
        ],
        'SMS · Funeral invitations' => [
            self::SMS_INVITATIONS_VIEW,
            self::SMS_INVITATIONS_SEND,
        ],
        'SMS · Post-funeral' => [
            self::SMS_POST_VIEW,
            self::SMS_POST_SEND,
        ],
        'SMS · Logs' => [
            self::SMS_LOGS_VIEW,
        ],
        'SMS · Contacts & Groups' => [
            self::CONTACTS_VIEW,
            self::CONTACTS_MANAGE,
            self::CONTACTS_IMPORT,
        ],
        'Branding' => [
            self::BRANDING_VIEW,
            self::BRANDING_EDIT,
        ],
        'Team (own tenant)' => [
            self::TEAM_VIEW,
            self::TEAM_CREATE,
            self::TEAM_DELETE,
        ],
        'Events schedule' => [
            self::EVENTS_VIEW,
            self::EVENTS_MANAGE,
        ],
        'Platform · Overview' => [
            self::PLATFORM_OVERVIEW,
        ],
        'Platform · Tenants' => [
            self::TENANTS_VIEW,
            self::TENANTS_CREATE,
            self::TENANTS_EDIT,
            self::TENANTS_DELETE,
            self::TENANTS_SWITCH,
        ],
        'Platform · Users' => [
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_DELETE,
            self::USERS_IMPERSONATE,
        ],
        'Platform · Roles' => [
            self::ROLES_VIEW,
            self::ROLES_EDIT,
        ],
        'Platform · Plans' => [
            self::PLANS_MANAGE,
        ],
        'Platform · Webhooks' => [
            self::WEBHOOKS_VIEW,
        ],
    ];

    /**
     * Human-readable descriptions for each permission.
     */
    public const DESCRIPTIONS = [
        self::DASHBOARD_VIEW => 'View the dashboard overview',

        self::DONATIONS_VIEW => 'View the donation history table',
        self::DONATIONS_CREATE => 'Take new donations (online & manual)',
        self::DONATIONS_VERIFY => 'Verify pending online donations against Paystack',

        self::SMS_NOTIFICATIONS_VIEW => 'Open the general SMS notifications page',
        self::SMS_NOTIFICATIONS_SEND => 'Send general SMS notifications',
        self::SMS_INVITATIONS_VIEW => 'Open the funeral invitations page',
        self::SMS_INVITATIONS_SEND => 'Send funeral invitation SMS',
        self::SMS_POST_VIEW => 'Open the post-funeral messages page',
        self::SMS_POST_SEND => 'Send post-funeral SMS',
        self::SMS_LOGS_VIEW => 'View the SMS campaign log',
        self::CONTACTS_VIEW => 'View saved contacts and contact groups',
        self::CONTACTS_MANAGE => 'Add, edit, delete contacts and groups (syncs to TextTango)',
        self::CONTACTS_IMPORT => 'Bulk import contacts from CSV or paste',

        self::BRANDING_VIEW => 'View branding settings',
        self::BRANDING_EDIT => 'Change tenant name, colors and images',

        self::TEAM_VIEW => 'View users belonging to your tenant',
        self::TEAM_CREATE => 'Invite new admins or users to your tenant',
        self::TEAM_DELETE => 'Remove users from your tenant',

        self::EVENTS_VIEW => 'View the funeral events schedule',
        self::EVENTS_MANAGE => 'Add, edit and remove events',

        self::PLATFORM_OVERVIEW => 'See cross-tenant platform statistics',
        self::TENANTS_VIEW => 'List all tenants',
        self::TENANTS_CREATE => 'Create new tenants',
        self::TENANTS_EDIT => 'Edit tenant settings',
        self::TENANTS_DELETE => 'Delete tenants',
        self::TENANTS_SWITCH => 'Switch into a tenant to act on its behalf',
        self::USERS_VIEW => 'List all users on the platform',
        self::USERS_CREATE => 'Create new users',
        self::USERS_DELETE => 'Delete users',
        self::USERS_IMPERSONATE => 'Impersonate another user to see the app from their view',
        self::ROLES_VIEW => 'View the roles & permissions reference',
        self::ROLES_EDIT => 'Change which permissions each non-super role has',
        self::PLANS_MANAGE => 'Configure subscription plans, limits and pricing',
        self::WEBHOOKS_VIEW => 'View webhook endpoints and recent inbound events',
    ];

    /**
     * The immutable defaults — used to seed the role_permissions table and as a
     * fallback if the table is empty or unavailable (e.g. before migrations run).
     */
    public static function defaultRoleMap(): array
    {
        $adminPerms = [
            self::DASHBOARD_VIEW,
            self::DONATIONS_VIEW,
            self::DONATIONS_CREATE,
            self::DONATIONS_VERIFY,
            self::SMS_NOTIFICATIONS_VIEW,
            self::SMS_NOTIFICATIONS_SEND,
            self::SMS_INVITATIONS_VIEW,
            self::SMS_INVITATIONS_SEND,
            self::SMS_POST_VIEW,
            self::SMS_POST_SEND,
            self::SMS_LOGS_VIEW,
            self::CONTACTS_VIEW,
            self::CONTACTS_MANAGE,
            self::CONTACTS_IMPORT,
            self::TEAM_VIEW,
            self::TEAM_CREATE,
            self::TEAM_DELETE,
            self::EVENTS_VIEW,
            self::EVENTS_MANAGE,
        ];

        return [
            User::ROLE_SUPER => self::all(),
            User::ROLE_ADMIN => $adminPerms,
            User::ROLE_USER => [
                self::DASHBOARD_VIEW,
                self::DONATIONS_VIEW,
                self::DONATIONS_CREATE,
                self::DONATIONS_VERIFY,
                self::EVENTS_VIEW,
            ],
        ];
    }

    private static ?array $roleMapCache = null;

    /**
     * The live role → permission map. Reads from the role_permissions table and
     * falls back to defaults for any role with no rows yet.
     */
    public static function roleMap(): array
    {
        if (self::$roleMapCache !== null) {
            return self::$roleMapCache;
        }

        $defaults = self::defaultRoleMap();
        $fromDb = [];
        try {
            $rows = DB::table('role_permissions')->select('role', 'permission')->get();
            foreach ($rows as $row) {
                $fromDb[$row->role][] = $row->permission;
            }
        } catch (\Throwable $e) {
            // Table doesn't exist yet — fall back to defaults.
            self::$roleMapCache = $defaults;
            return $defaults;
        }

        $map = $defaults;
        foreach ($fromDb as $role => $perms) {
            $map[$role] = array_values(array_unique($perms));
        }
        // Super role always has everything, regardless of DB rows.
        $map[User::ROLE_SUPER] = self::all();

        self::$roleMapCache = $map;
        return $map;
    }

    public static function saveForRole(string $role, array $permissions): void
    {
        if ($role === User::ROLE_SUPER) {
            return; // Super is always all-in via Gate::before — nothing to save.
        }
        $valid = array_values(array_intersect($permissions, self::all()));
        DB::transaction(function () use ($role, $valid) {
            DB::table('role_permissions')->where('role', $role)->delete();
            $now = now();
            $rows = array_map(
                fn ($p) => ['role' => $role, 'permission' => $p, 'created_at' => $now, 'updated_at' => $now],
                $valid,
            );
            if ($rows) {
                DB::table('role_permissions')->insert($rows);
            }
        });
        self::$roleMapCache = null;
    }

    public static function clearCache(): void
    {
        self::$roleMapCache = null;
    }

    public static function all(): array
    {
        return array_keys(self::DESCRIPTIONS);
    }

    public static function forRole(string $role): array
    {
        return self::roleMap()[$role] ?? [];
    }

    public static function userHas(User $user, string $permission): bool
    {
        if ($user->role === User::ROLE_SUPER) {
            return true;
        }

        return in_array($permission, self::forRole($user->role), true);
    }

    public static function label(string $permission): string
    {
        return self::DESCRIPTIONS[$permission] ?? ucwords(str_replace(['.', '_'], [' · ', ' '], $permission));
    }

    public static function roleColor(string $role): string
    {
        return match ($role) {
            User::ROLE_SUPER => '#ffb300',
            User::ROLE_ADMIN => '#66bb6a',
            User::ROLE_USER => '#64b5f6',
            default => '#ffffff',
        };
    }
}
