import React from 'react'
import { Outlet, useMatches } from 'react-router-dom'
import Sidebar from './Sidebar'
import TitleBar from './TitleBar'

export default function AppShell() {
    const matches = useMatches()
    const title = matches.FindLast((m) => m.handle?.title)?.handle?.title || 'HRIS'

    return (
        <div className="flex h-screen w-screen overflow-hidden bg-paper">
            <Sidebar />
            <div className="flex min-w-0 flex-1 flex-col">
                <TitleBar title={title} />
                <main className="flex-1 overflow-auto p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    )
}