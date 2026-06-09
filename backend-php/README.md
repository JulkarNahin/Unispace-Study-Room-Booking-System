# UniSpace PHP Backend

Run from inside `backend-php`:

```bash
php -S localhost:8000 -t . router.php
```

Main data file:

```text
data/db.json
```

Useful test URLs:

```text
http://localhost:8000/api/rooms
http://localhost:8000/api/bookings
```

The seed data follows the proposal mock-up: six rooms, eight reservations, and room-usage counts of 2, 2, 2, 1, 0, 0.
