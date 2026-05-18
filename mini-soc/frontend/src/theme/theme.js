import { alpha, createTheme } from '@mui/material/styles';

/**
 * Thème « SOC high-tech » : fond profond, accents électriques, glass & halos discrets.
 */
const bgDeep = '#030712';
const bgPaper = '#0a0f1a';
const electric = '#00e8ff';
const violet = '#a78bfa';
const gridLine = alpha(electric, 0.06);

const socTheme = createTheme({
  palette: {
    mode: 'dark',
    primary: {
      main: electric,
      light: '#5ef0ff',
      dark: '#00b8cc',
      contrastText: '#030712',
    },
    secondary: {
      main: violet,
      light: '#c4b5fd',
      dark: '#7c3aed',
      contrastText: '#030712',
    },
    background: {
      default: bgDeep,
      paper: bgPaper,
    },
    text: {
      primary: alpha('#f0f4fc', 0.95),
      secondary: alpha('#f0f4fc', 0.62),
      disabled: alpha('#f0f4fc', 0.38),
    },
    divider: alpha(electric, 0.14),
    success: { main: '#34d399' },
    warning: { main: '#fbbf24' },
    error: { main: '#f87171' },
  },
  shape: { borderRadius: 10 },
  typography: {
    fontFamily:
      '"Space Grotesk","Segoe UI","Helvetica Neue",Helvetica,Arial,sans-serif',
    fontWeightRegular: 400,
    fontWeightMedium: 500,
    fontWeightBold: 700,
    h3: {
      fontWeight: 700,
      letterSpacing: '-0.02em',
    },
    h4: {
      fontWeight: 700,
      letterSpacing: '-0.02em',
    },
    h5: {
      fontWeight: 600,
      letterSpacing: '-0.01em',
    },
    h6: {
      fontWeight: 600,
      letterSpacing: '0.02em',
    },
    subtitle1: { letterSpacing: '0.02em' },
    subtitle2: {
      fontFamily:
        '"JetBrains Mono","Fira Code",Consolas,monospace',
      letterSpacing: '0.06em',
      fontWeight: 500,
      textTransform: 'uppercase',
      fontSize: '0.72rem',
    },
    caption: {
      fontFamily:
        '"JetBrains Mono","Fira Code",Consolas,monospace',
      fontSize: '0.72rem',
      letterSpacing: '0.03em',
    },
    button: {
      letterSpacing: '0.08em',
      fontWeight: 600,
      textTransform: 'uppercase',
      fontSize: '0.8125rem',
    },
  },
  components: {
    MuiCssBaseline: {
      styleOverrides: {
        body: {
          backgroundColor: bgDeep,
          backgroundImage: `
            radial-gradient(ellipse 100% 60% at 50% -15%, ${alpha(electric, 0.14)}, transparent 55%),
            radial-gradient(ellipse 55% 40% at 100% 0%, ${alpha(violet, 0.09)}, transparent 50%),
            linear-gradient(${gridLine} 1px, transparent 1px),
            linear-gradient(90deg, ${gridLine} 1px, transparent 1px)
          `,
          backgroundSize: '100% 100%, 100% 100%, 40px 40px, 40px 40px',
          backgroundAttachment: 'fixed',
        },
      },
    },
    MuiButton: {
      defaultProps: { variant: 'contained', disableElevation: true },
      styleOverrides: {
        root: {
          borderRadius: 8,
          transition: 'box-shadow 160ms ease, transform 160ms ease, background 160ms ease',
          '&:hover': {
            boxShadow: `0 0 20px ${alpha(electric, 0.35)}`,
          },
        },
        containedPrimary: {
          background: `linear-gradient(135deg, ${electric} 0%, ${alpha('#06b6d4', 0.92)} 100%)`,
          color: bgDeep,
          '&:hover': {
            background: `linear-gradient(135deg, ${alpha('#5ef0ff', 1)} 0%, ${electric} 100%)`,
          },
        },
        outlined: {
          borderColor: alpha(electric, 0.45),
          '&:hover': {
            borderColor: electric,
            backgroundColor: alpha(electric, 0.06),
          },
        },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: {
          backgroundImage: 'none',
          border: `1px solid ${alpha(electric, 0.14)}`,
          boxShadow:
            `0 0 0 1px ${alpha('#000', 0.45)} inset, 0 12px 40px ${alpha('#000', 0.42)}`,
        },
      },
    },
    MuiCard: {
      styleOverrides: {
        root: {
          position: 'relative',
          overflow: 'hidden',
          border: `1px solid ${alpha(electric, 0.12)}`,
          backgroundImage: `
            radial-gradient(
              ellipse 80% 50% at 100% -20%,
              ${alpha(violet, 0.08)},
              transparent 55%
            )
          `,
        },
      },
    },
    MuiAppBar: {
      styleOverrides: {
        root: {
          backgroundImage: 'none',
          backgroundColor: alpha(bgDeep, 0.72),
          backdropFilter: 'blur(16px) saturate(160%)',
          WebkitBackdropFilter: 'blur(16px) saturate(160%)',
          borderBottom: `1px solid ${alpha(electric, 0.22)}`,
          boxShadow:
            `0 4px 24px ${alpha('#000', 0.35)}, 0 0 28px ${alpha(electric, 0.06)}`,
        },
      },
    },
    MuiDrawer: {
      styleOverrides: {
        paper: {
          backgroundColor: alpha('#050a14', 0.92),
          backgroundImage:
            `linear-gradient(180deg, ${alpha(electric, 0.06)} 0%, transparent 32%)`,
          borderRight: `1px solid ${alpha(electric, 0.16)}`,
          boxShadow:
            `${alpha(electric, 0.04)} -1px 0 24px inset`,
        },
      },
    },
    MuiListItemButton: {
      styleOverrides: {
        root: {
          borderRadius: 8,
          marginLeft: 8,
          marginRight: 8,
          marginBottom: 2,
          transition: 'background 160ms ease, box-shadow 160ms ease',
          '&:hover': {
            backgroundColor: alpha(electric, 0.08),
          },
          '&.Mui-selected': {
            background: `linear-gradient(90deg, ${alpha(electric, 0.2)} 0%, ${alpha(electric, 0.04)} 100%)`,
            borderLeft: `3px solid ${electric}`,
            paddingLeft: 13,
            boxShadow: `0 0 18px ${alpha(electric, 0.12)}`,
            '&:hover': {
              background: `linear-gradient(90deg, ${alpha(electric, 0.26)} 0%, ${alpha(electric, 0.08)} 100%)`,
            },
          },
        },
      },
    },
    MuiListItemIcon: {
      styleOverrides: {
        root: {
          color: alpha('#f0f4fc', 0.55),
          minWidth: 40,
        },
      },
    },
    MuiTextField: {
      defaultProps: { variant: 'outlined', size: 'small' },
    },
    MuiOutlinedInput: {
      styleOverrides: {
        root: {
          borderRadius: 8,
          transition: 'box-shadow 160ms ease, border-color 160ms ease',
          '& fieldset': {
            borderColor: alpha(electric, 0.25),
          },
          '&:hover fieldset': {
            borderColor: alpha(electric, 0.45),
          },
          '&.Mui-focused fieldset': {
            borderWidth: '1px',
            borderColor: electric,
          },
          '&.Mui-focused': {
            boxShadow: `0 0 0 3px ${alpha(electric, 0.14)}`,
          },
        },
      },
    },
    MuiDivider: {
      styleOverrides: {
        root: { borderColor: alpha(electric, 0.1) },
      },
    },
    MuiChip: {
      styleOverrides: {
        root: {
          fontFamily:
            '"JetBrains Mono","Fira Code",Consolas,monospace',
          fontSize: '0.72rem',
        },
      },
    },
    MuiAlert: {
      styleOverrides: {
        root: { borderRadius: 8 },
        standardError: {
          border: `1px solid ${alpha('#f87171', 0.35)}`,
          backgroundColor: alpha('#450a0a', 0.55),
        },
      },
    },
    MuiDataGrid: {
      styleOverrides: {
        root: {
          borderColor: alpha(electric, 0.12),
          '& .MuiDataGrid-cell:focus-within': {
            outline: `1px solid ${alpha(electric, 0.3)}`,
            outlineOffset: -1,
          },
          '& .MuiDataGrid-columnHeaders': {
            borderColor: alpha(electric, 0.12),
          },
          '& .MuiDataGrid-footerContainer': {
            borderColor: alpha(electric, 0.12),
          },
        },
      },
    },
    MuiLink: {
      styleOverrides: {
        root: {
          color: electric,
          textDecoration: 'none',
          '&:hover': {
            textDecoration: 'underline',
            textShadow: `0 0 12px ${alpha(electric, 0.45)}`,
          },
        },
      },
    },
  },
});

export default socTheme;
