# BunthyAI Web Chat Client

A single-page AI chat experience built with PHP and vanilla web tooling. The frontend runs entirely from `index.php` and talks to lightweight PHP endpoints that proxy requests to a hosted AI service and persist per-user chat history locally.

## Features

- Conversational UI with markdown rendering and syntax highlighting for code responses.
- Persistent chat history per browser via generated user IDs and JSON files.
- Voice interaction: speech-to-text input (where supported) and optional text-to-speech playback.
- File attachment preview with passthrough to the upstream AI API.
- Light/dark theme toggle, adjustable voice speed, and chat export.

## Tech Stack

- **Frontend:** Tailwind CSS (via CDN), DaisyUI, jQuery 3.x, Marked, Prism.js, Font Awesome.
- **Backend:** PHP (with cURL) providing `api/chat.php` and `api/history.php` endpoints.
- **Storage:** Local JSON files under `data/<user_id>/` for message history.

## Project Structure

```
.
├── api/
│   ├── chat.php        # Forwards chat requests to the remote AI API.
│   ├── config.php      # Central configuration (API URL, storage settings).
│   └── history.php     # CRUD operations for chat history JSON files.
├── data/               # Per-user chat history directories (git-ignored).
├── js/
│   └── app.js          # All client-side behaviour for the chat UI.
├── index.php           # Entry point serving the SPA UI.
└── README.md
```

## Getting Started

### Prerequisites

- PHP 8.0+ with the cURL extension enabled.
- A web server capable of serving PHP (Apache, Nginx with PHP-FPM, or PHP's built-in server).
- Internet access for the proxied AI API (`https://bj-tricks-ai.vercel.app/chat`).

### Installation

1. Place the project directory inside your web server's document root.
2. Ensure the web server user can read and write to the `data/` directory (it will be created automatically if missing).

### Local Development Server

Use PHP's built-in server for quick testing:

```bash
php -S localhost:8000 -t path/to/project
```

Then open `http://localhost:8000/index.php` in your browser.

## Configuration

Edit `api/config.php` to adjust:

- `API_URL` – upstream AI service endpoint (defaults to `https://bj-tricks-ai.vercel.app/chat`).
- `STORAGE_DIR` – filesystem path for chat history JSON files.
- `MAX_HISTORY_FILES` – soft limit for stored history files per user (enforced by your own logic as enhancements).

## Usage

1. Open the app in your browser. A unique `user_id` is generated and stored in `localStorage`.
2. Start a conversation via the message box. Each new conversation is persisted automatically.
3. Use the sidebar to revisit, rename (on first save), or delete chats.
4. Toggle voice responses, adjust speech speed, or export the active conversation from the settings menu.
5. Attach files to send them along with your prompt (subject to upstream API support).

## API Endpoints

| Endpoint            | Method | Description                                                        |
|---------------------|--------|--------------------------------------------------------------------|
| `api/chat.php`      | POST   | Forwards prompts (and optional file uploads) to the remote AI API. |
| `api/history.php`   | GET    | `action=list_history` lists chat summaries for the current user.    |
| `api/history.php`   | GET    | `action=get_chat&chat_id=<id>` returns the full chat payload.       |
| `api/history.php`   | POST   | `action=save_chat` saves/updates a chat session.                    |
| `api/history.php`   | POST   | `action=delete_chat` removes a stored chat.                         |

All `history.php` requests require a `user_id` parameter, which the frontend manages automatically.

## Data Storage

Chat histories are written as prettified JSON files in `data/<user_id>/<chat_id>.json`. Each file stores:

- `id` – chat identifier.
- `title` – display title shown in the sidebar.
- `updated_at` – UNIX timestamp for sorting history.
- `messages` – array of message objects containing rendered HTML and raw text.

Regularly prune the `data/` directory or extend the backend to enforce `MAX_HISTORY_FILES` to keep storage healthy.

## Contributing / Extending

- Add authentication around the API endpoints if hosting publicly.
- Implement rate limiting or request validation before proxying to the upstream AI.
- Consider moving configuration to environment variables for production deployments.
- Expand the Docs link (`Docs/index.html`) if you plan to distribute developer documentation.

## License

No license has been specified. If you plan to share this project, add an appropriate license file.
