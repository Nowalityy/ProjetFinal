import { useEffect, useMemo, useState } from 'react';
import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  Paper,
  Stack,
  TextField,
  Typography,
  MenuItem,
  Chip,
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import EditIcon from '@mui/icons-material/Edit';
import DeleteOutlineIcon from '@mui/icons-material/DeleteOutline';
import { DataGrid } from '@mui/x-data-grid';
import { apiClient } from '../api/client';
import { collectionItems } from '../utils/hydra';

const ROLE_OPTIONS = [
  { value: 'ROLE_AUDITEUR', label: 'Auditeur' },
  { value: 'ROLE_ANALYSTE', label: 'Analyste' },
  { value: 'ROLE_ADMIN', label: 'Administrateur' },
];

function emptyForm() {
  return {
    email: '',
    plainPassword: '',
    roles: ['ROLE_AUDITEUR'],
  };
}

function AdminUsersPage() {
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [open, setOpen] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [form, setForm] = useState(emptyForm());

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await apiClient.get('/users', { params: { itemsPerPage: 200 } });
      setRows(collectionItems(res.data));
    } catch (e) {
      setError(e.message || 'Impossible de charger les utilisateurs');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const columns = useMemo(() => [
    { field: 'id', headerName: 'ID', width: 70 },
    { field: 'email', headerName: 'Courriel', flex: 1, minWidth: 200 },
    {
      field: 'roles',
      headerName: 'Rôles',
      flex: 1,
      minWidth: 260,
      renderCell: ({ value }) => (
        <Stack direction="row" gap={0.5} flexWrap="wrap">
          {(Array.isArray(value) ? value : []).map((r) => (
            <Chip key={r} size="small" label={r.replace('ROLE_', '')} />
          ))}
        </Stack>
      ),
    },
    {
      field: 'actions',
      headerName: 'Actions',
      width: 140,
      sortable: false,
      filterable: false,
      renderCell: ({ row }) => (
        <Stack direction="row">
          <IconButton
            size="small"
            aria-label={`Modifier ${row.email}`}
            onClick={() => {
              setEditingId(row.id);
              setForm({
                email: row.email,
                plainPassword: '',
                roles: Array.isArray(row.roles) ? row.roles : [],
              });
              setOpen(true);
            }}
          >
            <EditIcon fontSize="small" />
          </IconButton>
          <IconButton
            size="small"
            aria-label={`Supprimer ${row.email}`}
            onClick={async () => {
              const okDelete = row.id !== 1 ? window.confirm(`Supprimer ${row.email} ?`) : window.confirm(`Supprimer l’admin #1 peut bloquer votre démo ; continuer ?`);
              if (!okDelete) return;
              try {
                await apiClient.delete(`/users/${row.id}`);
                await load();
              } catch (e) {
                setError(e.message || 'Suppression impossible');
              }
            }}
          >
            <DeleteOutlineIcon fontSize="small" />
          </IconButton>
        </Stack>
      ),
    },
  ], []);

  const closeDialog = () => {
    setOpen(false);
    setEditingId(null);
    setForm(emptyForm());
  };

  const save = async () => {
    const uniqRoles = [...new Set(form.roles)];
    const body = {
      email: form.email.trim(),
      roles: uniqRoles,
    };
    if (form.plainPassword.trim()) {
      body.plainPassword = form.plainPassword.trim();
    }
    try {
      if (editingId != null) {
        await apiClient.patch(`/users/${editingId}`, body, {
          headers: { 'Content-Type': 'application/merge-patch+json' },
        });
      } else {
        await apiClient.post('/users', body, {
          headers: { 'Content-Type': 'application/ld+json' },
        });
      }
      closeDialog();
      await load();
    } catch (e) {
      setError(e.response?.data?.detail || e.response?.data?.violations?.[0]?.message || e.message || 'Erreur');
    }
  };

  return (
    <Stack spacing={2}>
      <Stack direction="row" justifyContent="space-between" alignItems="center">
        <Typography variant="h5">Gestion des utilisateurs</Typography>
        <Button
          startIcon={<AddIcon />}
          variant="contained"
          onClick={() => {
            setEditingId(null);
            setForm(emptyForm());
            setOpen(true);
          }}
        >
          Nouveau compte
        </Button>
      </Stack>

      <Typography variant="body2" color="text.secondary">
        Réservé aux administrateurs SOC : ces comptes alimentent l’IAM interne JWT + API Platform.
      </Typography>

      {error ? <Alert severity="error" onClose={() => setError('')}>{error}</Alert> : null}

      <Paper sx={{ bgcolor: 'background.paper' }}>
        <DataGrid
          rows={rows}
          columns={columns}
          loading={loading}
          getRowId={(r) => r.id}
          autoHeight
          initialState={{
            pagination: { paginationModel: { pageSize: 10 } },
          }}
          pageSizeOptions={[10, 25]}
          sx={{ border: 0 }}
        />
      </Paper>

      <Dialog open={open} onClose={() => closeDialog()} maxWidth="sm" fullWidth>
        <DialogTitle>{editingId != null ? 'Modifier l’utilisateur' : 'Créer un utilisateur'}</DialogTitle>
        <DialogContent>
          <Stack spacing={2} sx={{ mt: 1 }}>
            <TextField
              label="Courriel"
              value={form.email}
              onChange={(ev) => setForm((f) => ({ ...f, email: ev.target.value }))}
              fullWidth
              required
              autoComplete="off"
            />
            <TextField
              label={editingId != null ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe initial'}
              type="password"
              value={form.plainPassword}
              onChange={(ev) => setForm((f) => ({ ...f, plainPassword: ev.target.value }))}
              fullWidth
              required={editingId == null}
              autoComplete="new-password"
            />
            <TextField
              select
              label="Rôles"
              value=""
              onChange={(ev) => {
                const v = ev.target.value;
                if (!v) return;
                setForm((f) => (
                  f.roles.includes(v)
                    ? f
                    : { ...f, roles: [...f.roles, v] }
                ));
              }}
              helperText="Ajoutez un rôle à la liste (cliquez plusieurs fois pour cumuler)."
              fullWidth
            >
              <MenuItem value="">Choisir un rôle…</MenuItem>
              {ROLE_OPTIONS.map((o) => (
                <MenuItem key={o.value} value={o.value}>
                  {o.label}
                </MenuItem>
              ))}
            </TextField>
            <Stack direction="row" gap={1} flexWrap="wrap">
              {form.roles.map((r) => (
                <Chip
                  key={r}
                  label={r}
                  onDelete={() => setForm((f) => ({
                    ...f,
                    roles: f.roles.filter((x) => x !== r),
                  }))}
                />
              ))}
            </Stack>
            {form.roles.length === 0 ? (
              <Alert severity="warning">Au moins un rôle est requis côté API.</Alert>
            ) : null}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => closeDialog()}>Annuler</Button>
          <Button
            onClick={() => save()}
            variant="contained"
            disabled={!form.email || (editingId == null && !form.plainPassword)}
          >
            Enregistrer
          </Button>
        </DialogActions>
      </Dialog>
    </Stack>
  );
}

export default AdminUsersPage;
