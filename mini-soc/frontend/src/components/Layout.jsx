import {
  Drawer, List, ListItemButton,
  ListItemIcon, ListItemText, Toolbar, AppBar,
  Typography, IconButton,
  Divider, Box,
} from '@mui/material';
import DashboardIcon from '@mui/icons-material/Dashboard';
import NotificationImportantIcon from '@mui/icons-material/NotificationImportant';
import ListAltIcon from '@mui/icons-material/ListAlt';
import PeopleIcon from '@mui/icons-material/People';
import AccountCircleIcon from '@mui/icons-material/AccountCircle';
import LogoutIcon from '@mui/icons-material/Logout';
import SensorsIcon from '@mui/icons-material/Sensors';
import { Link as RouterLink, Outlet, useLocation } from 'react-router-dom';
import { useMemo } from 'react';
import { alpha } from '@mui/material/styles';
import { useAuth, hasRole, ROLE } from '../context/AuthContext';

const drawerWidth = 264;

const navAll = [
  { to: '/dashboard', label: 'Dashboard', icon: DashboardIcon },
  { to: '/alerts', label: 'Alertes', icon: NotificationImportantIcon },
  { to: '/logs', label: 'Journaux', icon: ListAltIcon },
  { to: '/profile', label: 'Profil', icon: AccountCircleIcon },
];

function Layout() {
  const location = useLocation();
  const { roles, logout, user } = useAuth();

  const items = useMemo(() => (
    navAll.concat(
      hasRole(roles, [ROLE.ADMIN])
        ? [{ to: '/admin/users', label: 'Utilisateurs', icon: PeopleIcon }]
        : [],
    )
  ), [roles]);

  return (
    <Box sx={{ display: 'flex', minHeight: '100vh' }}>
      <AppBar position="fixed" sx={{ zIndex: (t) => t.zIndex.drawer + 1 }}>
        <Toolbar sx={{ px: { xs: 2, md: 3 }, minHeight: '64px !important' }}>
          <Box sx={{ flexGrow: 1, display: 'flex', alignItems: 'center', gap: 1.25 }}>
            <SensorsIcon
              sx={{
                color: 'primary.main',
                fontSize: 28,
                filter: (t) => `drop-shadow(0 0 8px ${alpha(t.palette.primary.main, 0.55)})`,
              }}
              aria-hidden
            />
            <Box>
              <Typography
                component="span"
                variant="h6"
                sx={{
                  fontWeight: 700,
                  letterSpacing: '0.12em',
                  textTransform: 'uppercase',
                  fontSize: '0.92rem',
                  background: (t) => `linear-gradient(90deg, ${t.palette.primary.light} 0%, ${t.palette.secondary.light} 100%)`,
                  backgroundClip: 'text',
                  WebkitBackgroundClip: 'text',
                  WebkitTextFillColor: 'transparent',
                }}
              >
                Mini SOC
              </Typography>
              <Typography
                variant="caption"
                component="span"
                sx={{
                  display: 'block',
                  mt: 0.2,
                  color: 'text.secondary',
                  letterSpacing: '0.08em',
                }}
              >
                Security Operations Console
              </Typography>
            </Box>
          </Box>
          <Box
            sx={{
              mr: 1,
              px: 1.5,
              py: 0.5,
              borderRadius: 999,
              border: (t) => `1px solid ${alpha(t.palette.primary.main, 0.25)}`,
              backgroundColor: alpha('#00e8ff', 0.06),
              maxWidth: { xs: 160, sm: 'none' },
            }}
          >
            <Typography
              variant="caption"
              sx={{
                display: 'block',
                fontFamily: '"JetBrains Mono",monospace',
                fontSize: '0.68rem',
                color: 'text.secondary',
              }}
              noWrap
              title={user?.email ?? ''}
            >
              {user?.email}
            </Typography>
          </Box>
          <IconButton
            color="inherit"
            onClick={() => logout()}
            aria-label="Déconnexion"
            sx={{
              ml: 0.5,
              border: (t) => `1px solid ${alpha(t.palette.divider, 0.45)}`,
              '&:hover': {
                bgcolor: alpha('#00e8ff', 0.08),
                boxShadow: (t) => `0 0 16px ${alpha(t.palette.primary.main, 0.25)}`,
              },
            }}
          >
            <LogoutIcon />
          </IconButton>
        </Toolbar>
      </AppBar>

      <Drawer
        variant="permanent"
        sx={{
          width: drawerWidth,
          flexShrink: 0,
          [`& .MuiDrawer-paper`]: {
            width: drawerWidth,
            boxSizing: 'border-box',
            top: 64,
            height: 'calc(100% - 64px)',
            pt: 1,
          },
        }}
      >
        <Typography
          variant="subtitle2"
          sx={{ px: 2.5, py: 1.5, color: 'text.secondary' }}
        >
          Navigation
        </Typography>
        <Divider sx={{ mx: 1.5 }} />
        <List sx={{ px: 0.5, pt: 1 }}>
          {items.map((item) => {
            const Icon = item.icon;
            const selected = location.pathname === item.to
              || location.pathname.startsWith(`${item.to}/`);
            return (
              <ListItemButton
                key={item.to}
                component={RouterLink}
                to={item.to}
                selected={selected}
              >
                <ListItemIcon
                  sx={{
                    color: selected ? 'primary.main' : 'inherit',
                    minWidth: 40,
                  }}
                >
                  <Icon fontSize={selected ? 'medium' : 'small'} />
                </ListItemIcon>
                <ListItemText
                  primary={item.label}
                  primaryTypographyProps={{
                    fontWeight: selected ? 600 : 500,
                    fontSize: '0.9rem',
                    letterSpacing: '0.03em',
                  }}
                />
              </ListItemButton>
            );
          })}
        </List>
      </Drawer>

      <Box
        component="main"
        sx={{
          flexGrow: 1,
          p: { xs: 2, md: 3 },
          ml: `${drawerWidth}px`,
          mt: '64px',
          bgcolor: 'transparent',
        }}
      >
        <Outlet />
      </Box>
    </Box>
  );
}

export default Layout;
