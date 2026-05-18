import { useParams, Link as RouterLink } from 'react-router-dom';
import { useEffect, useState, useMemo } from 'react';
import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  CircularProgress,
  MenuItem,
  Stack,
  TextField,
  Typography,
} from '@mui/material';
import { apiClient } from '../api/client';
import { hasRole, ROLE, useAuth } from '../context/AuthContext';
import { numericIdFromIri } from '../utils/hydra';

const STATUSES = [
  { value: 'open', label: 'Ouverte' },
  { value: 'in_progress', label: 'En cours' },
  { value: 'resolved', label: 'Résolue' },
  { value: 'false_positive', label: 'Faux positif' },
];

function AlertDetailPage() {
  const { id } = useParams();
  const { roles } = useAuth();
  const canEdit = hasRole(roles, [ROLE.ANALYSTE, ROLE.ADMIN]);
  const canComment = canEdit;

  const [alert, setAlert] = useState(null);
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [comment, setComment] = useState('');
  const [authLogEmbed, setAuthLogEmbed] = useState(null);

  const [form, setForm] = useState({
    status: '',
    description: '',
    assignedUserIri: '',
  });

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const [aRes, uRes] = await Promise.all([
        apiClient.get(`/alerts/${id}`),
        canEdit ? apiClient.get('/users', { params: { itemsPerPage: 100 } }) : Promise.resolve({ data: [] }),
      ]);
      const a = aRes.data;
      setAlert(a);
      setForm({
        status: String(a.status ?? ''),
        description: typeof a.description === 'string' ? a.description : '',
        assignedUserIri: a.assignedUser?.['@id'] ?? '',
      });
      if (Array.isArray(uRes.data['hydra:member'])) {
        setUsers(uRes.data['hydra:member']);
      } else if (Array.isArray(uRes.data)) {
        setUsers(uRes.data);
      }
    } catch (e) {
      setError(e.response?.status === 403 ? 'Accès refusé à cette alerte.' : (e.message || 'Erreur'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  const comments = useMemo(() => {
    if (!alert?.comments) {
      return [];
    }
    return Array.isArray(alert.comments) ? alert.comments : [];
  }, [alert]);

  useEffect(() => {
    if (!alert) {
      setAuthLogEmbed(null);
      return undefined;
    }
    const embedded = alert.authLog;
    if (embedded && typeof embedded === 'object' && embedded.ip) {
      setAuthLogEmbed(null);
      return undefined;
    }
    const iri = typeof embedded === 'string' ? embedded : embedded?.['@id'];
    const num = numericIdFromIri(iri);
    if (!num) {
      setAuthLogEmbed(null);
      return undefined;
    }
    const ac = new AbortController();
    (async () => {
      try {
        const r = await apiClient.get(`/auth_logs/${num}`, { signal: ac.signal });
        setAuthLogEmbed(r.data);
      } catch {
        setAuthLogEmbed(null);
      }
    })();
    return () => ac.abort();
  }, [alert]);

  const authDisplay = useMemo(() => {
    const embedded = alert?.authLog;
    if (embedded && typeof embedded === 'object' && embedded.ip) {
      return embedded;
    }
    return authLogEmbed;
  }, [alert, authLogEmbed]);

  const save = async () => {
    if (!canEdit) {
      return;
    }
    setSaving(true);
    setError('');
    try {
      const body = {
        status: form.status,
        description: form.description,
        assignedUser: form.assignedUserIri || null,
      };
      await apiClient.patch(`/alerts/${id}`, body, {
        headers: { 'Content-Type': 'application/merge-patch+json' },
      });
      await load();
    } catch (e) {
      setError(e.message || 'Échec de la mise à jour');
    } finally {
      setSaving(false);
    }
  };

  const postComment = async () => {
    if (!comment.trim()) {
      return;
    }
    setSaving(true);
    setError('');
    try {
      await apiClient.post('/alert_comments', {
        content: comment.trim(),
        alert: `/api/alerts/${id}`,
      }, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      setComment('');
      await load();
    } catch (e) {
      setError(e.response?.status === 403
        ? 'Seuls analystes et admins peuvent commenter.'
        : (e.message || 'Erreur'));
    } finally {
      setSaving(false);
    }
  };

  if (loading && !alert) {
    return (
      <Stack alignItems="center" py={6}>
        <CircularProgress />
      </Stack>
    );
  }

  if (error && !alert) {
    return (
      <Stack spacing={2}>
        <Button component={RouterLink} to="/alerts">
          ← Retour aux alertes
        </Button>
        <Alert severity="error">{error}</Alert>
      </Stack>
    );
  }

  return (
    <Stack spacing={3}>
      <Stack direction="row" alignItems="center" spacing={2}>
        <Button component={RouterLink} to="/alerts">
          ← Retour
        </Button>
        <Typography variant="h5">
          Alerte #{id}
        </Typography>
      </Stack>
      {error ? <Alert severity="warning">{error}</Alert> : null}

      <Card sx={{ bgcolor: 'background.paper' }}>
        <CardContent component={Stack} spacing={2}>
          <Typography variant="subtitle2" color="text.secondary">
            Type · sévérité
          </Typography>
          <Typography variant="body1">
            {[String(alert?.type ?? ''), String(alert?.severity ?? '')].filter(Boolean).join(' · ')
              || '—'}
          </Typography>
          <Typography variant="subtitle2" color="text.secondary">
            Journal relié (IP)
          </Typography>
          <Typography variant="body2">
            {authDisplay?.ip ?? '—'}
            {' · '}
            {authDisplay?.status ?? ''}
          </Typography>

          <TextField
            label="Description"
            value={form.description}
            onChange={(ev) => setForm((s) => ({ ...s, description: ev.target.value }))}
            multiline
            minRows={3}
            disabled={!canEdit}
            fullWidth
          />
          <TextField
            select
            label="Statut"
            value={form.status}
            onChange={(ev) => setForm((s) => ({ ...s, status: ev.target.value }))}
            disabled={!canEdit}
            fullWidth
          >
            {STATUSES.map((opt) => (
              <MenuItem key={opt.value} value={opt.value}>
                {opt.label}
              </MenuItem>
            ))}
          </TextField>
          <TextField
            select
            label="Assigné à"
            value={form.assignedUserIri}
            onChange={(ev) => setForm((s) => ({
              ...s,
              assignedUserIri: ev.target.value,
            }))}
            disabled={!canEdit}
            fullWidth
            helperText="Choisissez un utilisateur SOC interne ou laissez vide."
          >
            <MenuItem value="">
              Non assignée
            </MenuItem>
            {users.map((u) => (
              <MenuItem key={u.id} value={u['@id'] ?? `/api/users/${u.id}`}>
                {u.email ?? u.id}
              </MenuItem>
            ))}
          </TextField>

          {canEdit ? (
            <Button variant="contained" onClick={() => save()} disabled={saving}>
              {saving ? 'Enregistrement…' : 'Enregistrer'}
            </Button>
          ) : (
            <Alert severity="info">Rôle auditeur : lecture seule sur les alertes.</Alert>
          )}
        </CardContent>
      </Card>

      <Card sx={{ bgcolor: 'background.paper' }}>
        <CardContent>
          <Typography variant="h6" gutterBottom>
            Commentaires
          </Typography>
          <Stack spacing={2}>
            {comments.length === 0 ? (
              <Typography variant="body2" color="text.secondary">
                Aucun commentaire encore.
              </Typography>
            ) : comments.map((c) => (
              <Box key={c.id ?? c['@id']} sx={{ pb: 1, borderBottom: '1px solid rgba(255,255,255,0.08)' }}>
                <Typography variant="caption" color="text.secondary">
                  {(c.author && (c.author.email || c.author['@id'])) || 'Utilisateur'}{' '}
                  · {String(c.createdAt ?? '')}
                </Typography>
                <Typography variant="body2">{c.content}</Typography>
              </Box>
            ))}
            {canComment ? (
              <Stack spacing={1}>
                <TextField
                  label="Ajouter une note SOC"
                  value={comment}
                  onChange={(ev) => setComment(ev.target.value)}
                  multiline
                  minRows={2}
                />
                <Button variant="outlined" onClick={() => postComment()} disabled={saving}>
                  Poster le commentaire
                </Button>
              </Stack>
            ) : null}
          </Stack>
        </CardContent>
      </Card>
    </Stack>
  );
}

export default AlertDetailPage;
