import { useState } from 'react';
import {
  Alert, Button, Paper, Stack, TextField, Typography,
} from '@mui/material';
import Link from '@mui/material/Link';
import { alpha } from '@mui/material/styles';
import SensorsIcon from '@mui/icons-material/Sensors';
import { Navigate, Link as RouterLink, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

function LoginPage() {
  const { login, isAuthenticated } = useAuth();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  if (isAuthenticated) {
    const redirectTo = location.state?.from?.pathname || '/dashboard';
    return <Navigate to={redirectTo} replace />;
  }

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await login(email.trim(), password);
    } catch (err) {
      const status = err.response?.status;
      const body = err.response?.data;
      const detail = typeof body?.message === 'string' ? body.message
        : (typeof body?.detail === 'string' ? body.detail : null);

      let msg;
      if (status === 401) {
        msg = 'Identifiants invalides.';
      } else if (status === 429) {
        msg = detail || 'Trop de tentatives sur /api/login ; patientez quelques secondes.';
      } else if (status === undefined) {
        msg = 'Erreur réseau ou serveur inaccessible. Astuce : utilisez http://localhost:8080 '
          + '(Nginx), ou après `docker compose` redémarrez le service frontend.';
      } else if (detail) {
        msg = `${status} : ${detail}`;
      } else {
        msg = `Erreur serveur (${status ?? 'sans code'}). Vérifiez les logs du conteneur backend.`;
      }
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Stack
      sx={{
        minHeight: '100vh',
        alignItems: 'center',
        justifyContent: 'center',
        p: 2,
        position: 'relative',
        '&::before': {
          content: '""',
          position: 'absolute',
          inset: 0,
          pointerEvents: 'none',
          background: 'radial-gradient(circle at 50% 80%, rgba(167,139,250,0.12) 0%, transparent 45%)',
        },
      }}
    >
      <Paper
        elevation={0}
        sx={{
          position: 'relative',
          width: '100%',
          maxWidth: 440,
          p: { xs: 3, sm: 4 },
          backdropFilter: 'blur(18px)',
          WebkitBackdropFilter: 'blur(18px)',
          bgcolor: alpha('#0a0f1a', 0.72),
          border: (t) => `1px solid ${alpha(t.palette.primary.main, 0.35)}`,
          boxShadow:
            (t) => `0 0 0 1px ${alpha(t.palette.primary.main, 0.08)} inset, 0 24px 64px rgba(0,0,0,0.55), 0 0 80px ${alpha(t.palette.primary.main, 0.08)}`,
        }}
      >
        <Stack direction="row" alignItems="center" spacing={1.5} sx={{ mb: 2 }}>
          <SensorsIcon
            sx={{
              fontSize: 38,
              color: 'primary.main',
              filter: (t) => `drop-shadow(0 0 12px ${alpha(t.palette.primary.main, 0.6)})`,
            }}
          />
          <Box>
            <Typography
              variant="h5"
              sx={{
                fontWeight: 700,
                letterSpacing: '0.08em',
                textTransform: 'uppercase',
                background: (t) => `linear-gradient(90deg, ${t.palette.primary.light}, ${t.palette.secondary.light})`,
                backgroundClip: 'text',
                WebkitBackgroundClip: 'text',
                WebkitTextFillColor: 'transparent',
              }}
            >
              Mini SOC
            </Typography>
            <Typography variant="subtitle2" color="text.secondary" sx={{ mt: 0.25 }}>
              Authentification sécurisée
            </Typography>
          </Box>
        </Stack>

        {error ? (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        ) : null}
        <form onSubmit={handleSubmit}>
          <Stack spacing={2.25}>
            <TextField
              label="Courriel"
              type="email"
              value={email}
              onChange={(ev) => setEmail(ev.target.value)}
              autoComplete="username"
              required
              fullWidth
              autoFocus
            />
            <TextField
              label="Mot de passe"
              type="password"
              value={password}
              onChange={(ev) => setPassword(ev.target.value)}
              autoComplete="current-password"
              required
              fullWidth
            />
            <Button type="submit" variant="contained" disabled={loading} fullWidth size="medium">
              {loading ? 'Connexion…' : 'Accès console'}
            </Button>
            <Typography variant="caption" color="text.secondary" align="center" sx={{ lineHeight: 1.65 }}>
              Compte démo (fixtures)&nbsp;: admin@minisoc.local / Admin12345!
            </Typography>
            <Typography variant="caption" align="center" sx={{ opacity: 0.85 }}>
              Redirection automatique vers le{' '}
              <Link component={RouterLink} to="/dashboard">tableau de bord</Link>
              {' '}selon votre session.
            </Typography>
          </Stack>
        </form>
      </Paper>
    </Stack>
  );
}

export default LoginPage;
