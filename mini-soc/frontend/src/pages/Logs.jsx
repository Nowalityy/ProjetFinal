import { useEffect, useMemo, useState } from 'react';
import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Paper,
  Stack,
  Typography,
} from '@mui/material';
import DownloadIcon from '@mui/icons-material/Download';
import { DataGrid } from '@mui/x-data-grid';
import axios from 'axios';
import { apiClient } from '../api/client';
import { collectionItems } from '../utils/hydra';
import { ROLE, hasRole, useAuth } from '../context/AuthContext';

function LogsPage() {
  const { roles } = useAuth();
  const canExport = hasRole(roles, [ROLE.ANALYSTE, ROLE.ADMIN]);
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const ac = new AbortController();
    (async () => {
      setLoading(true);
      setError('');
      try {
        const res = await apiClient.get('/auth_logs', {
          params: { itemsPerPage: 300, 'order[createdAt]': 'desc' },
          signal: ac.signal,
        });
        const mapped = collectionItems(res.data).map((r) => ({
          id: r.id,
          ip: r.ip ?? '',
          status: String(r.status ?? ''),
          emailHash: r.emailHash ?? '',
          createdAt: r.createdAt ? String(r.createdAt) : '',
          userAgent: typeof r.userAgent === 'string'
            ? (r.userAgent.length > 80 ? `${r.userAgent.slice(0, 80)}…` : r.userAgent)
            : '',
        })).filter((r) => r.id != null);
        setRows(mapped);
      } catch (e) {
        if (!axios.isCancel(e)) {
          setError(e.message || 'Impossible de charger les journaux');
        }
      } finally {
        setLoading(false);
      }
    })();
    return () => ac.abort();
  }, []);

  const columns = useMemo(() => [
    { field: 'id', headerName: 'ID', width: 70 },
    { field: 'createdAt', headerName: 'Horodatage', flex: 1, minWidth: 180 },
    { field: 'ip', headerName: 'IP', width: 130 },
    { field: 'status', headerName: 'Statut', width: 100 },
    { field: 'emailHash', headerName: 'Hash email', flex: 1, minWidth: 200 },
    { field: 'userAgent', headerName: 'UA', flex: 1, minWidth: 160 },
  ], []);

  const exportCsv = async () => {
    const res = await apiClient.get('/export/logs.csv', { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'logs.csv';
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <Stack spacing={2}>
      <Stack direction="row" justifyContent="space-between" alignItems="center">
        <Typography variant="h5">
          Journaux d’authentification
        </Typography>
        {canExport ? (
          <Button startIcon={<DownloadIcon />} onClick={() => exportCsv()} variant="outlined">
            Export CSV
          </Button>
        ) : null}
      </Stack>
      {error ? <Alert severity="error">{error}</Alert> : null}
      <Paper sx={{ width: '100%', bgcolor: 'background.paper' }}>
        {loading ? (
          <Box py={6} display="flex" justifyContent="center">
            <CircularProgress />
          </Box>
        ) : (
          <DataGrid
            autoHeight
            rows={rows}
            columns={columns}
            pageSizeOptions={[10, 25, 50]}
            initialState={{
              pagination: { paginationModel: { pageSize: 25 } },
              sorting: { sortModel: [{ field: 'createdAt', sort: 'desc' }] },
            }}
            sx={{ border: 0 }}
          />
        )}
      </Paper>
    </Stack>
  );
}

export default LogsPage;
