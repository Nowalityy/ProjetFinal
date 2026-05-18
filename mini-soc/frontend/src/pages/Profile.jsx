import { Card, CardContent, Divider, Stack, Typography, Button } from '@mui/material';
import { useAuth } from '../context/AuthContext';

function ProfilePage() {
  const { user, roles, logout } = useAuth();

  return (
    <Stack spacing={2} maxWidth={640}>
      <Typography variant="h5">Profil</Typography>
      <Card sx={{ bgcolor: 'background.paper' }}>
        <CardContent component={Stack} spacing={2}>
          <Typography variant="subtitle2" color="text.secondary">Courriel</Typography>
          <Typography variant="body1">{user?.email ?? '—'}</Typography>
          <Divider flexItem sx={{ opacity: 0.3 }} />
          <Typography variant="subtitle2" color="text.secondary">Rôles dérivés du JWT</Typography>
          <Typography variant="body2">{roles.join(', ') || 'Aucun'}</Typography>
          <Typography variant="caption" color="text.secondary">
            La modification détaillée du compte (email / mot de passe) passe par les
            administrateurs SOC via « Utilisateurs » ou par les workflows internes côté backend.
          </Typography>
          <Button variant="outlined" color="secondary" onClick={() => logout()}>
            Déconnexion
          </Button>
        </CardContent>
      </Card>
    </Stack>
  );
}

export default ProfilePage;
