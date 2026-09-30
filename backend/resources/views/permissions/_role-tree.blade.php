@foreach ($treeRoles as $role)
    <div class="role-node" data-role-node="{{ $role->slug }}">
        <div draggable="false" data-role="{{ $role->slug }}" data-parent="{{ $role->parent_slug }}" style="--role-color: {{ $role->warna ?: '#2563eb' }}" class="role-item flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3 {{ $selectedRole->slug === $role->slug ? 'border-brand-300 bg-brand-50' : '' }}">
            @if ($canCreate && $role->slug !== 'owner')<span draggable="true" data-drag-handle class="material-symbols-outlined role-drag-handle text-slate-400" title="Tarik untuk memindahkan role">drag_indicator</span>@else<span class="material-symbols-outlined text-amber-500">workspace_premium</span>@endif
            <a href="{{ route('admin.permissions.index', ['role' => $role->slug]) }}" class="role-link min-w-0 flex-1"><span class="role-name block truncate">{{ $role->nama }}</span><span class="role-parent block truncate">Parent: {{ $role->parent_slug ? ($allRoles->firstWhere('slug', $role->parent_slug)?->nama ?? $role->parent_slug) : 'Root' }}</span></a>
            @if ($canCreate && ! $role->is_system && $role->slug !== 'owner')
                <button type="button" data-role-edit="{{ $role->slug }}" class="material-symbols-outlined text-slate-400 hover:text-brand-600" title="Edit role">edit</button>
                <form method="POST" action="{{ route('admin.permissions.roles.destroy', $role->slug) }}" onsubmit="return confirm('Hapus role {{ addslashes($role->nama) }}?')" class="inline-flex">@csrf @method('DELETE')<button type="submit" class="material-symbols-outlined text-slate-400 hover:text-rose-600" title="Hapus role">delete</button></form>
            @endif
        </div>
        <div class="role-children" data-parent-container="{{ $role->slug }}">
            @include('permissions._role-tree', ['treeRoles' => $allRoles->where('parent_slug', $role->slug), 'allRoles' => $allRoles, 'selectedRole' => $selectedRole, 'canCreate' => $canCreate])
        </div>
    </div>
@endforeach