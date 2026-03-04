# root

## manuel installation

```bash
docker compose up -d
```

le CDN est mis sous `cdn.localhost:8080`, et le vhost PROD sous `prod.localhost:8080`.

MVC:

- prod/
  - index.php
  - dashboard.php
  - login.php
  - register.php
- control/
  - ControllerStudent.php
  - ControllerPilote.php
  - ControllerOffer.php
  - ControllerCompany.php
  - ControllerAnnouncement
  - ControllerFile.php
  - ControllerPreferences.php
  - ControllerWishlist.php
  - ControllerActivitySector.php
- model/
  - StudentRepo.php
    - `setName($id, $firstName, $lastName)`
    - `setAge($id, $)`
    - `set`
  - 