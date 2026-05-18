import {
  Box,
  Card, CardContent, Grid, Stack, Typography, CircularProgress,
} from '@mui/material';
import { alpha, useTheme } from '@mui/material/styles';
import {
  CartesianGrid,
  ResponsiveContainer,
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip as RTooltip,
  BarChart,
  Bar,
  Legend,
  PieChart,
  Pie,
  Cell,
} from 'recharts';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../api/client';

function StatCard({ title, value, subtitle }) {
  return (
    <Card sx={{ height: '100%', bgcolor: 'background.paper' }}>
      <CardContent>
        <Typography color="text.secondary" gutterBottom variant="subtitle2">
          {title}
        </Typography>
        <Typography
          variant="h4"
          sx={{
            fontFamily: '"JetBrains Mono","Fira Code",monospace',
            fontWeight: 600,
            letterSpacing: '-0.02em',
          }}
        >
          {value}
        </Typography>
        {subtitle ? (
          <Typography variant="caption" color="text.secondary">
            {subtitle}
          </Typography>
        ) : null}
      </CardContent>
    </Card>
  );
}

function DashboardPage() {
  const theme = useTheme();

  const piePalette = [
    theme.palette.primary.main,
    theme.palette.secondary.main,
    alpha(theme.palette.primary.main, 0.65),
    theme.palette.error.main,
  ];

  const chartAxis = {
    stroke: theme.palette.text.secondary,
    tick: { fill: theme.palette.text.secondary, fontSize: 11 },
  };

  const { data, error, isLoading } = useQuery({
    queryKey: ['dashboard', 'stats'],
    queryFn: async () => {
      const res = await apiClient.get('/dashboard/stats');
      return res.data;
    },
  });

  if (error) {
    return (
      <Typography color="error">
        Impossible de charger les statistiques ({error.message}). Vérifiez votre rôle ou le JWT.
      </Typography>
    );
  }

  if (isLoading || !data) {
    return (
      <Stack alignItems="center" py={6}>
        <CircularProgress />
      </Stack>
    );
  }

  const { counters } = data;
  const hourly = Array.isArray(data.charts?.attemptsHourly7d)
    ? data.charts.attemptsHourly7d
    : [];
  const sevSeries = Array.isArray(data.charts?.alertsBySeverity30d)
    ? data.charts.alertsBySeverity30d
    : [];

  const statusObj = data.charts?.alertStatusShares ?? {};
  const pieData = Object.entries(statusObj).map(([name, value]) => ({ name, value }));

  const topIps = Array.isArray(data.topIps) ? data.topIps.slice(0, 8) : [];

  const successPct = counters?.successRate24h !== undefined && counters?.successRate24h !== null
    ? `${(Number(counters.successRate24h) * 100).toFixed(1)}`
    : '—';

  const fmtHourLabel = (h) => (h ? String(h).replace('T', ' ').slice(0, 16) : '');

  return (
    <Stack spacing={3}>
      <Box>
        <Typography
          variant="h5"
          sx={{
            fontWeight: 700,
            letterSpacing: '0.06em',
            textTransform: 'uppercase',
            background: `linear-gradient(90deg, ${theme.palette.primary.light} 0%, ${theme.palette.secondary.light} 85%)`,
            backgroundClip: 'text',
            WebkitBackgroundClip: 'text',
            WebkitTextFillColor: 'transparent',
          }}
        >
          Vue opérationnelle
        </Typography>
        <Typography variant="subtitle2" color="text.secondary" sx={{ mt: 0.5 }}>
          Agrégats live — événements d’authentification & alertes
        </Typography>
      </Box>
      <Grid container spacing={2}>
        <Grid size={{ xs: 12, sm: 6, md: 3 }}>
          <StatCard title="Alertes actives" value={String(counters?.activeAlerts ?? '—')} />
        </Grid>
        <Grid size={{ xs: 12, sm: 6, md: 3 }}>
          <StatCard
            title="Tentatives 24 h"
            value={String(counters?.authAttempts24h ?? '—')}
          />
        </Grid>
        <Grid size={{ xs: 12, sm: 6, md: 3 }}>
          <StatCard title="IP uniques (24 h)" value={String(counters?.uniqueIp24h ?? '—')} />
        </Grid>
        <Grid size={{ xs: 12, sm: 6, md: 3 }}>
          <StatCard
            title="Taux succès 24 h"
            value={`${successPct}%`}
            subtitle="Tentatives SSH / web étudiées dans le jeu de données fixtures"
          />
        </Grid>
      </Grid>

      <Card sx={{ p: 2, bgcolor: 'background.paper' }}>
        <Typography variant="subtitle1" gutterBottom>
          Tentatives d’authentification (7 derniers jours, par fenêtre serveur)
        </Typography>
        <div style={{ width: '100%', height: 320 }}>
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={hourly}>
              <CartesianGrid
                strokeDasharray="3 3"
                stroke={alpha(theme.palette.primary.main, 0.22)}
              />
              <XAxis
                dataKey="hour"
                tickFormatter={(v) => fmtHourLabel(v)}
                angle={-30}
                textAnchor="end"
                interval="preserveStartEnd"
                height={60}
                {...chartAxis}
              />
              <YAxis {...chartAxis} />
              <RTooltip labelFormatter={(v) => fmtHourLabel(v)} />
              <Legend wrapperStyle={{ color: theme.palette.text.secondary }} />
              <Line
                type="monotone"
                dataKey="attempts"
                name="Tentatives"
                stroke={theme.palette.primary.main}
                strokeWidth={2}
                dot={false}
              />
            </LineChart>
          </ResponsiveContainer>
        </div>
      </Card>

      <Grid container spacing={2}>
        <Grid size={{ xs: 12, lg: 6 }}>
          <Card sx={{ p: 2, height: '100%', bgcolor: 'background.paper' }}>
            <Typography variant="subtitle1" gutterBottom>
              Alertes par sévérité (30 derniers jours)
            </Typography>
            <div style={{ width: '100%', height: 300 }}>
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={sevSeries}>
                  <CartesianGrid
                    strokeDasharray="3 3"
                    stroke={alpha(theme.palette.primary.main, 0.18)}
                  />
                  <XAxis dataKey="severity" {...chartAxis} />
                  <YAxis {...chartAxis} />
                  <RTooltip />
                  <Legend wrapperStyle={{ color: theme.palette.text.secondary }} />
                  <Bar dataKey="count" name="Alertes" fill={theme.palette.secondary.main} radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </Card>
        </Grid>
        <Grid size={{ xs: 12, lg: 6 }}>
          <Card sx={{ p: 2, height: '100%', bgcolor: 'background.paper' }}>
            <Typography variant="subtitle1" gutterBottom>
              Répartition des statuts d’alerte (toutes périodes)
            </Typography>
            <div style={{ width: '100%', height: 300 }}>
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={pieData}
                    dataKey="value"
                    nameKey="name"
                    outerRadius={100}
                    label
                  >
                    {pieData.map((entry, idx) => (
                      <Cell
                        key={entry.name}
                        fill={piePalette[idx % piePalette.length]}
                      />
                    ))}
                  </Pie>
                  <RTooltip />
                  <Legend wrapperStyle={{ color: theme.palette.text.secondary }} />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </Card>
        </Grid>
      </Grid>

      <Card sx={{ p: 2, bgcolor: 'background.paper' }}>
        <Typography variant="subtitle1" gutterBottom>
          Principales sources IP par volume de tentatives
        </Typography>
        <ResponsiveContainer width="100%" height={Math.max(topIps.length * 42, 120)}>
          <BarChart layout="vertical" data={topIps}>
            <CartesianGrid
              strokeDasharray="3 3"
              horizontal={false}
              stroke={alpha(theme.palette.primary.main, 0.15)}
            />
            <XAxis type="number" {...chartAxis} />
            <YAxis type="category" dataKey="ip" width={120} {...chartAxis} />
            <RTooltip
              formatter={(v, name) => [v, name]}
              labelFormatter={(l) => `IP ${l}`}
            />
            <Legend wrapperStyle={{ color: theme.palette.text.secondary }} />
            <Bar dataKey="attempts" name="Tentatives" fill={theme.palette.primary.main} radius={[0, 4, 4, 0]} />
            <Bar dataKey="failures" name="Échecs" fill={theme.palette.error.main} radius={[0, 4, 4, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </Card>
    </Stack>
  );
}

export default DashboardPage;
