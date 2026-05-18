import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

/** @typedef {{ roles?: string[] }} ProtectedRouteProps */

function ProtectedRoute({ roles }) {
  const { isAuthenticated, roles: mine } = useAuth();
  const location = useLocation();

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (roles && roles.length > 0) {
    const ok = roles.some((r) => mine.includes(r));
    if (!ok) {
      return <Navigate to="/dashboard" replace />;
    }
  }

  return <Outlet />;
}

export default ProtectedRoute;
