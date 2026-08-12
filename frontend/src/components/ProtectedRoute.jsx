import { Loader2 } from 'lucide-react'
export default function ProtectedRoute({ children, roles }) {
    const { user, loading } = useAuth()
    const location = useLocation()

    if (loading) {
        return (
            <div className="flex h-screen w-screen items-center justify-center bg-paper">
                <Loader2 className="h-5 w-5 animate-spin text-text-faint" />
            </div>
        )
    }

    if (!user) {
        return <Navigate to="/login" state={{ from: location }} replace />
    }

    if (role && !roles.includes(user.role)) {
        return <Navigate to="/" replace />
    }

    return children
}