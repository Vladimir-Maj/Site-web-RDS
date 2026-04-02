// ... tes autres tests (testFindByIdWithInvalidValue, testInsert, etc.)


    protected function tearDown(): void
{
    self::$pdo->exec("
        DELETE u, s, se
        FROM user u
        LEFT JOIN student s  ON u.id_user = s.id_student
        LEFT JOIN student_enrollment se ON s.id_student = se.student_id_student_enrollment
        WHERE u.email_user LIKE '%@test.com'
    ");
}

    public function testSearchStudentsHappyPath(): void
{
    $repo = new \App\Repository\UserRepository(self::$pdo);

    // 1. Création d'un étudiant via push()
    $user = \App\Models\UserModel::fromArray([
        'email_user'      => 'jean.search@test.com',
        'password'        => password_hash('test', PASSWORD_ARGON2ID),
        'first_name_user' => 'Jean',
        'last_name_user'  => 'Recherche',
        'is_active_user'  => true,
        'role'            => 'student',
    ]);

    $userId = $repo->push($user);
    $this->assertNotNull($userId, "L'utilisateur aurait dû être inséré");
    $this->assertGreaterThan(0, $userId, "L'ID retourné doit être un entier positif");

    // On vérifie que la ligne student existe bien avant de tester la recherche.
    $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM student WHERE id_student = ?');
    $stmt->execute([(int) $userId]);
    $this->assertEquals(1, $stmt->fetchColumn(), "La ligne student doit exister après push()");

    // 2. On filtre la recherche
    $filters = [
        'name'   => 'Recherche',
        'status' => 'searching',
        'limit'  => 10,
        'page'   => 1,
    ];

    // 3. On teste la méthode
    $results = $repo->searchStudents($filters);

    $this->assertIsArray($results);
    $this->assertNotEmpty($results, "La recherche devrait trouver l'étudiant");

    // 4. On vérifie que c'est bien le bon étudiant avec les bonnes clés
    $found = false;
    foreach ($results as $row) {
        if ((int) $row['id'] === (int) $userId) {
            $found = true;
            $this->assertEquals('jean.search@test.com', $row['email']);
            $this->assertEquals('searching', $row['status']);
            $this->assertArrayHasKey('first_name', $row);
            $this->assertArrayHasKey('last_name', $row);
        }
    }
    $this->assertTrue($found, "L'étudiant inséré n'a pas été trouvé dans les résultats de recherche");
}




public function testUpdateStudentStatus(): void
{
    $repo = new \App\Repository\UserRepository(self::$pdo);

    // 1. Création d'un étudiant
   
    $user = \App\Models\UserModel::fromArray([
        'email_user'      => 'bob.status@test.com',
        'password'        => password_hash('test', PASSWORD_ARGON2ID),
        'first_name_user' => 'Bob',
        'last_name_user'  => 'Status',
        'is_active_user'  => true,
        'role'            => 'student', 
    ]);

    $userId = $repo->push($user);
    $this->assertNotNull($userId, "L'utilisateur aurait dû être inséré");



    // 2. Vérification du statut initial avant modification
    $stmt = self::$pdo->prepare('SELECT status_student FROM student WHERE id_student = ?');
    $stmt->execute([(int) $userId]);
    $initialStatus = $stmt->fetchColumn();
    $this->assertEquals('searching', $initialStatus, "Le statut initial doit être 'searching'");

    // 3. On change le statut via la méthode du repo
    $updated = $repo->updateStudentStatus($userId, 'hired');
    $this->assertTrue($updated, "La mise à jour devrait retourner true");

    // 4. On vérifie directement en BDD que le changement est persisté
    $stmt = self::$pdo->prepare('SELECT status_student FROM student WHERE id_student = ?');
    $stmt->execute([(int) $userId]);
    $newStatus = $stmt->fetchColumn();

    $this->assertEquals('hired', $newStatus, "Le statut en BDD devrait être 'hired' après la mise à jour");
}
    
    