# DoaFarma

Plataforma de doacao de medicamentos que conecta medicos (doadores) com receptores. O projeto consiste em um monorepo com duas aplicacoes principais:

- **`web/`** — Backend API + Painel Admin (Laravel 12 + Filament 5)
- **`mobile/`** — App Mobile (React Native + Expo SDK 54)

## Pre-requisitos

| Ferramenta | Versao |
|------------|--------|
| PHP | 8.5+ |
| Node.js | 18+ |
| PostgreSQL | 17 |
| Composer | 2.x |
| npm | 9+ |
| Docker | (recomendado) |

## Quick Start

```bash
# 1. Clone o repositorio
git clone https://github.com/jcfurtado86/doafarma.git
cd doafarma

# 2. Setup Backend
cd web
cp .env.example .env
composer install
docker compose up -d                # Sobe PostgreSQL
php artisan key:generate
php artisan migrate
composer dev                        # Inicia todos os servicos

# 3. Setup Mobile (em outro terminal)
cd mobile
npm install
npx expo start
```

## Setup Detalhado

### Backend (Laravel)

```bash
cd web

# Instalar dependencias
composer install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Banco de dados (Docker)
docker compose up -d

# Configurar .env com credenciais do banco
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=doafarma
# DB_USERNAME=postgres
# DB_PASSWORD=postgres

# Rodar migrations
php artisan migrate

# (Opcional) Seed com dados de teste
php artisan db:seed

# Iniciar servidor de desenvolvimento
composer dev
```

O comando `composer dev` inicia simultaneamente:
- Servidor Laravel (http://localhost:8000)
- Queue listener
- Log viewer (Pail)
- Vite (assets frontend)

### Mobile (React Native/Expo)

```bash
cd mobile

# Instalar dependencias
npm install

# Configurar API host
# Crie um arquivo .env.local com:
# EXPO_PUBLIC_API_HOST=SEU_IP_LOCAL

# Iniciar Expo
npx expo start
```

Para rodar no dispositivo/emulador:
- **Android**: `npm run android`
- **iOS**: `npm run ios`

## Comandos Uteis

### Web (Laravel)

| Comando | Descricao |
|---------|-----------|
| `composer dev` | Inicia todos os servicos de desenvolvimento |
| `composer t` | Roda testes (Pest) |
| `composer tp` | Testes em paralelo |
| `composer td` | Testes apenas arquivos modificados |
| `composer analyse` | Analise estatica (PHPStan) |
| `composer pint` | Formata codigo (Laravel Pint) |
| `composer fix` | Rector + PHPStan + Pint + Testes |

### Mobile (React Native)

| Comando | Descricao |
|---------|-----------|
| `npx expo start` | Inicia servidor Expo |
| `npm run android` | Inicia no Android |
| `npm run ios` | Inicia no iOS |
| `npm test` | Roda testes (Jest) |
| `npm run check-types` | Verifica tipos TypeScript |
| `npm run lint` | Roda ESLint |
| `npm run lint:fix` | ESLint com autofix |

## Arquitetura

```
doafarma/
├── web/                    # Backend Laravel
│   ├── app/
│   │   ├── Actions/        # Logica de negocio por dominio
│   │   ├── Filament/       # Painel admin (Resources, Widgets)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/  # Controllers single-action
│   │   │   └── Resources/  # API Resources
│   │   ├── Models/
│   │   └── Policies/
│   └── ...
│
└── mobile/                 # App React Native
    ├── app/                # Rotas (Expo Router)
    │   └── (auth)/         # Telas autenticadas
    ├── components/         # Componentes reutilizaveis
    ├── services/           # Chamadas API
    ├── stores/             # Estado global (Zustand)
    └── types/              # Tipos TypeScript
```

### Papeis de Usuario

- **doctor** — Medicos que doam medicamentos (app mobile)
- **receptor** — Receptores que recebem doacoes (app mobile)
- **admin** — Administradores (painel web Filament)

## Links Uteis

| Recurso | URL |
|---------|-----|
| API Docs | http://localhost:8000/docs/api |
| Admin Panel | http://localhost:8000/admin |

## Licenca

Projeto academico - TCC
