<div class="space-y-4">
    @if($users->isEmpty())
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                <x-heroicon-o-users class="w-8 h-8 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">No users found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                This tenant has no users yet. Use "Create Admin User" to add one.
            </p>
        </div>
    @else
        {{-- Summary --}}
        <div class="grid grid-cols-4 gap-3 mb-2">
            @php
                $roleCounts = $users->groupBy('role')->map->count();
                $roleColors = [
                    'admin' => 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:ring-purple-800',
                    'manager' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800',
                    'staff' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800',
                    'teller' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
                ];
            @endphp
            @foreach(['admin', 'manager', 'staff', 'teller'] as $role)
                <div class="rounded-xl ring-1 {{ $roleColors[$role] ?? 'bg-gray-50 text-gray-700 ring-gray-200' }} p-3 text-center">
                    <div class="text-2xl font-bold">{{ $roleCounts->get($role, 0) }}</div>
                    <div class="text-xs font-medium uppercase tracking-wide mt-0.5">{{ ucfirst($role) }}s</div>
                </div>
            @endforeach
        </div>

        {{-- Users Table --}}
        <div class="overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Last Login</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                        <span class="text-sm font-bold text-primary-700 dark:text-primary-300">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeColor = match($user->role) {
                                        'admin' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                        'manager' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                        'staff' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'teller' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                    {{ \App\Models\Tenant\User::ROLES[$user->role] ?? ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-red-700 dark:text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-xs text-gray-400 dark:text-gray-500 text-center pt-1">
            Showing {{ $users->count() }} user(s) in <strong>{{ $tenant->name }}</strong>
        </div>
    @endif
</div>
