<?php
$users = [
    ['name' => 'Ferdinand Sanchez II', 'email' => 'ferdinance@tkwebapp.com', 'password' => 'ferdinandpass'],
    ['name' => 'Marion Navarro', 'email' => 'marion@tkwebapp.com', 'password' => 'marionpass'],
    ['name' => 'Joshua Barbo', 'email' => 'joshua@tkwebapp.com', 'password' => 'joshuapass'],
    ['name' => 'Mildred Collado', 'email' => 'mildred@tkwebapp.com', 'password' => 'mildredpass'],
    ['name' => 'Eunice Santiago', 'email' => 'eunice@tkwebapp.com', 'password' => 'eunicepass'],
    ['name' => 'Isaac Hernandez', 'email' => 'isaac@tkwebapp.com', 'password' => 'isaacpass'],
    ['name' => 'Raf Ranin', 'email' => 'raf@tkwebapp.com', 'password' => 'rafpass'],
    ['name' => 'Chester Carreon', 'email' => 'chester@tkwebapp.com', 'password' => 'chesterpass'],
    ['name' => 'Aaron Sumampong', 'email' => 'aaron@tkwebapp.com', 'password' => 'aaronpass'],
    ['name' => 'Enrico Villegas', 'email' => 'enrico@tkwebapp.com', 'password' => 'enricopass'],
];

echo "<pre>";
foreach ($users as $user) {
    $hash = password_hash($user['password'], PASSWORD_DEFAULT);
    echo "UPDATE tk_webapp.users SET password_hash = '$hash' WHERE user_email = '{$user['email']}';\n";
}
echo "</pre>";
?>
