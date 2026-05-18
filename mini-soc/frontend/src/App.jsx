import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { CssBaseline, ThemeProvider } from '@mui/material';
import Layout from './components/Layout.jsx';
import ProtectedRoute from './components/ProtectedRoute.jsx';
import socTheme from './theme/theme.js';
import { AuthProvider, ROLE } from './context/AuthContext';

import LoginPage from './pages/Login.jsx';
import DashboardPage from './pages/Dashboard.jsx';
import AlertsPage from './pages/Alerts.jsx';
import AlertDetailPage from './pages/AlertDetail.jsx';
import LogsPage from './pages/Logs.jsx';
import ProfilePage from './pages/Profile.jsx';
import AdminUsersPage from './pages/Admin.jsx';

function App() {
  return (
    <ThemeProvider theme={socTheme}>
      <CssBaseline />
      <AuthProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route element={<ProtectedRoute />}>
              <Route element={<Layout />}>
                <Route path="/" element={<Navigate to="/dashboard" replace />} />
                <Route path="/dashboard" element={<DashboardPage />} />
                <Route path="/alerts" element={<AlertsPage />} />
                <Route path="/alerts/:id" element={<AlertDetailPage />} />
                <Route path="/logs" element={<LogsPage />} />
                <Route path="/profile" element={<ProfilePage />} />
                <Route element={<ProtectedRoute roles={[ROLE.ADMIN]} />}>
                  <Route path="/admin/users" element={<AdminUsersPage />} />
                </Route>
              </Route>
            </Route>
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </BrowserRouter>
      </AuthProvider>
    </ThemeProvider>
  );
}

export default App;
