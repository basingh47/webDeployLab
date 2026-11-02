# SSH key: how GitHub Actions logs in to the server

The deploy workflow ([.github/workflows/deploy.yml](.github/workflows/deploy.yml)) logs in to the VM over SSH to copy `public/build` and run [docker/deploy.sh](docker/deploy.sh). It needs a key to do that.

## How it works

A key is a **pair** of files:

```
              ssh-keygen makes the pair
                         │
          ┌──────────────┴──────────────┐
     PRIVATE key                    PUBLIC key
     ~/.ssh/github_deploy           ~/.ssh/github_deploy.pub
          │                              │
          ▼                              ▼
     GitHub secret SSH_KEY          VM: ~/.ssh/authorized_keys
     (GitHub uses it to log in)     (VM lets in whoever has the matching private key)
```

At deploy time:

```
GitHub machine (has private key) ──ssh basingh47@35.238.125.125──▶ VM
                                   VM checks ~/.ssh/authorized_keys
                                   matching public key found → login OK
```

| File | Starts with | Secret? | Lives in |
|---|---|---|---|
| Private `github_deploy` | `-----BEGIN OPENSSH PRIVATE KEY-----` | **Yes, never share it** | GitHub secret `SSH_KEY` only |
| Public `github_deploy.pub` | `ssh-ed25519 AAAA... github-actions` | No | VM `~/.ssh/authorized_keys` |

- Only the private key can log in. The public key can only check it, so the public key is safe to show anyone.
- The key can be made on any computer. Here it's made on the VM, so nothing has to be copied between machines.
- `~/.ssh` is on the **VM** (`/home/basingh47/.ssh`), not inside a container. Containers have no SSH.

## Setup (once)

### 1. On the VM: make the key and allow it

```bash
ssh-keygen -t ed25519 -f ~/.ssh/github_deploy -N "" -C github-actions
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
ssh -i ~/.ssh/github_deploy -o StrictHostKeyChecking=no basingh47@localhost 'echo ok'   # must print: ok
```

| Command | What it does |
|---|---|
| `ssh-keygen -t ed25519` | Makes the pair (ed25519 is a modern, short, secure key type) |
| `-f ~/.ssh/github_deploy` | File name for the private key. The public key gets `.pub` added |
| `-N ""` | No passphrase. GitHub can't type one |
| `-C github-actions` | A label at the end of the public key line, to recognise it later |
| `>> authorized_keys` | **Adds** the public key as a new line. A single `>` would replace the file |
| `chmod 600` | Only you can read or write the file. SSH ignores the file if the permissions are too open |
| `ssh ... localhost 'echo ok'` | Tests the key by logging in to the VM itself |

### 2. On GitHub: save the private key as a secret

```bash
cat ~/.ssh/github_deploy      # on the VM
```

Copy **everything**, from `-----BEGIN OPENSSH PRIVATE KEY-----` to `-----END OPENSSH PRIVATE KEY-----`.
Paste it **only** into GitHub, not into chats, notes or commits.

Go to the repo → **Settings** → **Secrets and variables** → **Actions** → **Secrets** tab → **New repository secret**:

| Name | Value |
|---|---|
| `SSH_HOST` | `35.238.125.125` |
| `SSH_USER` | `basingh47` |
| `SSH_KEY` | the whole private key |

Use **Repository** secrets, not Environment secrets or Variables. The workflow reads them as `${{ secrets.SSH_KEY }}`.

### 3. Delete the private key from the VM

GitHub keeps its copy, and the VM only needs the public key in `authorized_keys`:

```bash
rm ~/.ssh/github_deploy
```

### 4. Test

Go to **Actions** → the latest run → **Re-run all jobs** (or push a commit). Every step should show ✅.

## If the private key leaks (or to replace it)

If the key was pasted somewhere, committed, or shown on screen, replace it:

```bash
sed -i '/github-actions$/d' ~/.ssh/authorized_keys      # old key can no longer log in
rm -f ~/.ssh/github_deploy ~/.ssh/github_deploy.pub
```

Then repeat **Setup** steps 1–3 and update the `SSH_KEY` secret in GitHub. Open the secret and click **Update**.

## Errors

| Error in the Actions log | Cause | Fix |
|---|---|---|
| `can't connect without a private SSH key or password` | `SSH_KEY` secret missing or empty | Add the secret (Setup step 2) |
| `ssh: no key found` | Only part of the key was pasted | Paste again, including the `BEGIN` / `END` lines |
| `unable to authenticate` / `Permission denied (publickey)` | Public key not in `authorized_keys`, or wrong `SSH_USER` | Check `cat ~/.ssh/authorized_keys` on the VM; `SSH_USER` must be `basingh47` |
| `i/o timeout` / `connection refused` | Wrong `SSH_HOST`, or the VM is stopped | Check the IP and that the VM is running |

If the key in `authorized_keys` doesn't work, add the public key in **GCP Console → VM → Edit → SSH Keys** instead. **Change the last word to `basingh47`**, because GCP uses the last word as the username.
