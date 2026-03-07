# Moodle Plugin — local_f2freport

📊 **Face-to-face Report** plugin for Moodle.
Provides a simple reporting page for **Face-to-face sessions** (from `mod_facetoface`) with user-friendly filters.

---

## ⚙️ Requirements
- **Moodle:** 4.1 LTS+
- **Plugin:** `mod_facetoface` (any version)

---

## 🏷️ Compatibilité

| Version Moodle | Statut |
|----------------|--------|
| 4.1 LTS        | ✅ Supportée (cible principale) |
| 4.5 LTS        | ✅ Supportée |
| 5.0            | ✅ Supportée |

---

## 🚀 Features
- List of sessions with key information
- Integration with Moodle capability system
- Multilingual support (English / Français)
- Secure access (requires login and capability `local/f2freport:viewreport`)
- GDPR compliant (no personal data stored)

---

## 📥 Installation

### Méthode 1 : Installation via Git (recommandée)

```bash
cd /path/to/moodle/local
git clone git@github.com:carlosdev-ops/moodle-local_f2freport.git f2freport
```

### Méthode 2 : Téléchargement manuel

1. Téléchargez ou clonez ce dépôt.
2. Placez le dossier dans : `moodle/local/f2freport`
3. Accédez à **Administration du site → Notifications** pour finaliser l'installation.
4. Videz les caches Moodle.

---

## 🔑 Capabilities
- `local/f2freport:viewreport` → View the report (required).

---

## 🔄 Workflow Git

```bash
# Développement
git clone git@github.com:carlosdev-ops/moodle-local_f2freport.git
git checkout -b feature/ma-fonctionnalite

# Mise à jour en production
git -C /var/www/moodle/local/f2freport pull origin main
```

---

## 🌍 Languages
- **English** (default)
- **Français**

---

## 📜 License
[GNU GPL v3](https://www.gnu.org/licenses/gpl-3.0.html)
You are free to use, modify, and share this plugin under the same license.
