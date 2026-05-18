import { useNavigate } from 'react-router-dom';
import { useMemo, useEffect, useState } from 'react';
import {
  Button,
  CircularProgress,
  Paper,
  Stack,
  Typography,
  Alert,
  Box,
} from '@mui/material';
import DownloadIcon from '@mui/icons-material/Download';
import { DataGrid } from '@mui/x-data-grid';
import axios from 'axios';
import { apiClient } from '../api/client';
import { collectionItems } from '../utils/hydra';
import { ROLE, hasRole, useAuth } from '../context/AuthContext';

function AlertsPage() {
  const navigate = useNavigate();
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
        const res = await apiClient.get('/alerts', {
          params: { itemsPerPage: 200, 'order[createdAt]': 'desc' },
          signal: ac.signal,
        });
        const mapped = collectionItems(res.data).map((r) => ({
          id: r.id,
          severity: String(r.severity ?? ''),
          status: String(r.status ?? ''),
          type: String(r.type ?? ''),
          createdAt: r.createdAt ? String(r.createdAt) : '',
          descriptionSnippet: typeof r.description === 'string'
            ? (r.description.length > 72 ? `${r.description.slice(0, 72)}…` : r.description)
            : '',
        })).filter((row) => row.id != null);
        setRows(mapped);
      } catch (e) {
        if (axios.isCancel(e)) {
          return;
        }
        setError(e.message || 'Erreur de chargement');
      } finally {
        setLoading(false);
      }
    })();
    return () => ac.abort();
  }, []);

  const columns = useMemo(() => [
    { field: 'id', headerName: 'ID', width: 70 },
    { field: 'severity', headerName: 'Sévérité', width: 110 },
    { field: 'status', headerName: 'Statut', width: 120 },
    { field: 'type', headerName: 'Type', width: 160 },
    { field: 'createdAt', headerName: 'Créée', flex: 1, minWidth: 180 },
    { field: 'descriptionSnippet', headerName: 'Description', flex: 2, minWidth: 200 },
  ], []);

  const exportCsv = async () => {
    const res = await apiClient.get('/export/alerts.csv', { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'alerts.csv';
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <Stack spacing={2}>
      <Stack direction="row" alignItems="center" justifyContent="space-between">
        <Typography variant="h5">Alertes</Typography>
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
            initialState={{ pagination: { paginationModel: { pageSize: 25 } } }}
            onRowClick={(p) => navigate(`/alerts/${p.id}`)}
            sx={{ border: 0, cursor: 'pointer' }}
          />
        )}
      </Paper>
    </Stack>
  );
}

export default AlertsPage;
