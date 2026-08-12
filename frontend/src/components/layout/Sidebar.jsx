import React from 'react'
import { NavLink } from 'react-router-dom'
import { LayoutGrid, Users, Building2, ShieldCheck, Logout, } from 'lucide-react'
import { cn } from '@/lib/utils'
import { useAuth } from '@/context/AuthContext'
import { Avatar, AvatarFallBack } from '@/components/ui/avatar'
import { initials, roleBadgeVariant } from '@/lib/format'
import { Badge } from '@components/ui/badge'

const NAV = [
    { to: '/', label: 'Dashboard', icon: LayoutGrid, end: true },
    { to: '/employees', label: 'Employees', icon: Users },
    { to: '/departments', label: 'Departments', icon: Building2 },
    { to: '/users', label: 'Accounts', icon: ShieldCheck, roles: ['SysAdmin', 'User'] },
]

export default function Sidebar() {
    const { user, logout } = useAuth()

    return (
        <aside className="flex h-full">
            {/* */}
        </aside>
    )
}