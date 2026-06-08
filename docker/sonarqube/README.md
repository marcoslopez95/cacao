# SonarQube — CACAO

SonarQube Community Build con el plugin `mc1arke/sonarqube-community-branch-plugin`
para análisis de ramas y Pull Requests.

> El Community Build oficial **no soporta** análisis de ramas/PRs de forma nativa.
> El plugin de mc1arke habilita esa funcionalidad sin cambiar de edición.

---

## Versiones pineadas

| Componente | Versión |
|------------|---------|
| SonarQube Community Build | `26.5.0.122743` |
| mc1arke branch plugin | `26.5.0` |
| PostgreSQL | `16.3-alpine3.20` |

> **Regla de compatibilidad**: La versión **major.minor** del plugin DEBE
> coincidir con la de SonarQube. Si actualizas SonarQube a `26.6.x`,
> descarga el plugin `26.6.x` correspondiente.

---

## Prerrequisitos del host

SonarQube requiere estos valores del kernel. Ejecútalos una vez por sesión
de arranque (o agrégalos a `/etc/sysctl.conf` para que persistan):

```bash
sudo sysctl -w vm.max_map_count=524288
sudo sysctl -w fs.file-max=131072
```

---

## Paso 1 — Descargar el JAR del plugin

1. Abre la página de releases del plugin:
   ```
   https://github.com/mc1arke/sonarqube-community-branch-plugin/releases
   ```

2. Busca la release que coincida con el major.minor de SonarQube (`25.1`).

3. Descarga el archivo `.jar`:
   ```
   sonarqube-community-branch-plugin-26.5.0.jar
   ```

4. **Colócalo en esta carpeta** (`docker/sonarqube/plugins/`):
   ```bash
   mv ~/Downloads/sonarqube-community-branch-plugin-26.5.0.jar \
      docker/sonarqube/plugins/
   ```

5. Verifica que el nombre del JAR coincide exactamente con lo definido en
   `SONAR_WEB_JAVAOPTS` y `SONAR_CE_JAVAOPTS` del `docker-compose.yml`.
   Si descargaste una versión distinta, actualiza esos dos env vars.

> Los JARs están en `.gitignore` — no se versionan.

---

## Paso 2 — Levantar los servicios

```bash
# Desde la raíz del proyecto
docker compose -f docker/sonarqube/docker-compose.yml up -d

# Ver logs (el primer arranque tarda ~2 min)
docker compose -f docker/sonarqube/docker-compose.yml logs -f sonarqube
```

SonarQube estará disponible en **http://localhost:9000**

Credenciales por defecto: `admin` / `admin` (te pedirá cambiarlas al primer login).

---

## Paso 3 — Configuración post-arranque

### 3.1 URL base del servidor

En **Administration → General → Server base URL**, configura la URL
pública que GitHub usará para hacer callbacks:

```
sonar.core.serverBaseURL = http://tu-servidor:9000
```

(En local puede ser `http://localhost:9000`; en producción usa la URL real.)

### 3.2 Crear el proyecto

1. **Projects → Create project → Create manually**
2. Project key: `cacao`
3. Display name: `CACAO`

### 3.3 Crear el token de análisis (SONAR_TOKEN)

1. **My Account → Security → Generate Token**
2. Tipo: *Project Analysis Token*, proyecto: `cacao`
3. Copia el valor — se usará como `SONAR_TOKEN` en GitHub Secrets.

### 3.4 Binding a GitHub (para decoración de PRs)

1. **Administration → DevOps Platform Integrations → GitHub**
2. Crea una **GitHub App** en tu organización con permisos:
   - *Repository: Pull requests* (read & write)
   - *Repository: Checks* (read & write)
3. Completa los campos: App ID, Client ID, Client Secret, Private Key.
4. En tu proyecto SonarQube: **Project Settings → DevOps Platform Integration**
   → selecciona la GitHub App y el repositorio.

### 3.5 GitHub Secrets requeridos

En tu repositorio GitHub → **Settings → Secrets and variables → Actions**:

| Secret | Valor |
|--------|-------|
| `SONAR_TOKEN` | Token generado en el paso 3.3 |
| `SONAR_HOST_URL` | URL del servidor SonarQube (ej. `http://tu-servidor:9000`) |

> Si el servidor SonarQube es local, necesitarás un tunnel (ngrok, Cloudflare
> Tunnel, etc.) para que GitHub Actions pueda alcanzarlo.

---

## Comandos útiles

```bash
# Estado de los contenedores
docker compose -f docker/sonarqube/docker-compose.yml ps

# Parar sin borrar datos
docker compose -f docker/sonarqube/docker-compose.yml stop

# Parar y eliminar contenedores (los volúmenes persisten)
docker compose -f docker/sonarqube/docker-compose.yml down

# Eliminar TODO incluyendo volúmenes (reset completo)
docker compose -f docker/sonarqube/docker-compose.yml down -v

# Ver logs en tiempo real
docker compose -f docker/sonarqube/docker-compose.yml logs -f
```

---

## Verificar que el plugin cargó correctamente

En **Administration → System → Installed Plugins** busca
*"Community Branch Plugin"*. Si no aparece, revisa los logs de arranque:

```bash
docker compose -f docker/sonarqube/docker-compose.yml logs sonarqube | grep -i "branch\|plugin\|error"
```

Errores comunes:
- `ClassNotFoundException` → el JAR no está en `plugins/` o el nombre no coincide
- `UnsatisfiedLinkError` → versión del plugin incompatible con SonarQube
