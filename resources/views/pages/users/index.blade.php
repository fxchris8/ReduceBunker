@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b flex items-center justify-between">
            <h1 class="text-xl font-bold">User Management</h1>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-x-auto overflow-y-auto max-h-[80vh]">
                <table class="w-full text-sm text-left border border-gray-200 rounded">
                    <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">Name</th>
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">Username</th>
                            {{-- <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">SSO ID</th> --}}
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">Current Role</th>
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50">
                                <td class="px-4 py-3 border border-gray-200 font-semibold">{{ $user->name }}</td>
                                <td class="px-4 py-3 border border-gray-200">{{ $user->username }}</td>
                                {{-- <td class="px-4 py-3 border border-gray-200 text-gray-500 text-xs">{{ $user->sso_id ?? '-' }}</td> --}}
                                <td class="px-4 py-3 border border-gray-200">
                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $user->role === 'admin' || $user->role === 'developer' ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-700' }}">
                                        {{ strtoupper($user->role ?: 'USER') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    @if(auth()->id() !== $user->id)
                                    <form action="{{ route('users.updateRole', $user) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-semibold">
                                            Save
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-xs text-gray-400 italic">Cannot change own role</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-gray-400 text-center">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
