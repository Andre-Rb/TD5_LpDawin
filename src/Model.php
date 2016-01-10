<?php

class Model
{
    protected $pdo;

    public function __construct(array $config)
    {
        try {
            if ($config['engine'] == 'mysql') {
                $this->pdo = new \PDO(
                    'mysql:dbname=' . $config['database'] . ';host=' . $config['host'],
                    $config['user'],
                    $config['password']
                );
                $this->pdo->exec('SET CHARSET UTF8');
            } else {
                $this->pdo = new \PDO(
                    'sqlite:' . $config['file']
                );
            }
        } catch (\PDOException $error) {
            throw new ModelException('Unable to connect to database');
        }
    }

    /**
     * Tries to execute a statement, throw an explicit exception on failure
     */
    protected function execute(\PDOStatement $query, array $variables = array())
    {
        if (!$query->execute($variables)) {
            $errors = $query->errorInfo();
            throw new ModelException($errors[2]);
        }
        return $query;
    }

    /**
     * Inserting a book in the database
     * Returns the numbers of copies inserted
     */
    public function insertBook($title, $author, $synopsis, $image, $copies)
    {
        $query = $this->pdo->prepare('INSERT INTO livres (titre, auteur, synopsis, image)
            VALUES (?, ?, ?, ?)');

        $this->execute($query, array($title, $author, $synopsis, $image));

        $lastInsertedId = $this->getOneBookWithParams($title, $author)['id'];
        $totalInsert = 0;

        for ($i = 0; $i < $copies; $i++) {
            $query = $this->pdo->prepare('INSERT INTO exemplaires (book_id) VALUES (?)');

            $this->execute($query, array($lastInsertedId));
            $totalInsert++;
        }

        return $totalInsert;
    }

    /**
     * Getting all the books
     */
    public function getBooks()
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres');

        $this->execute($query);

        return $query->fetchAll();
    }

    /**
     * Getting one particular book
     */
    public function getOneBook($id)
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres WHERE id=?');

        $this->execute($query, array($id));

        return $query->fetchAll()[0];
    }

    /**
     * Getting one particular book
     */
    public function getOneBookWithParams($title, $auteur)
    {
        $query = $this->pdo->prepare('
            SELECT livres.* FROM livres
            WHERE titre=?
            AND auteur=?
        ');

        $this->execute($query, array($title, $auteur));

        return $query->fetchAll()[0];
    }


    /**
     * Get all copies of a book
     */
    public function getAllCopiesOfABookById($id)
    {
        $query = $this->pdo->prepare('
            SELECT library.exemplaires.* FROM livres
            WHERE library.exemplaires.book_id=?
        ');

        $this->execute($query, array($id));

        return $query->fetchAll();
    }
}
