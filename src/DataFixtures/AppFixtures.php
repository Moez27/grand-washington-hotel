<?php

namespace App\DataFixtures;

use App\Entity\Amenity;
use App\Entity\Category;
use App\Entity\HotelService;
use App\Entity\Reservation;
use App\Entity\Review;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Jeu de données de démonstration :
 *   php bin/console doctrine:fixtures:load
 *
 * Comptes :
 *   admin@grandwashington.tn  / Admin123!
 *   client@grandwashington.tn / Client123!
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads')] private readonly string $uploadDir,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        mt_srand(2026);
        $faker->seed(2026);

        // Nettoyage des anciennes images générées
        $fs = new Filesystem();
        foreach (['rooms', 'services'] as $dir) {
            $fs->mkdir($this->uploadDir.'/'.$dir);
            foreach (glob($this->uploadDir.'/'.$dir.'/demo-*') ?: [] as $f) {
                $fs->remove($f);
            }
        }

        // ---------- Catégories ----------
        $categoriesData = [
            'Standard' => ['bi-door-open', 'Des chambres lumineuses et fonctionnelles, idéales pour un court séjour.', ['#2d4a5a', '#7fa6b5']],
            'Supérieure' => ['bi-star', 'Plus d\'espace et une décoration raffinée inspirée de la Méditerranée.', ['#5b4636', '#c9a66b']],
            'Deluxe' => ['bi-gem', 'Vue imprenable, matériaux nobles et prestations haut de gamme.', ['#1f3b4d', '#d4b483']],
            'Suite' => ['bi-stars', 'Salon séparé, terrasse privée et service personnalisé.', ['#2b2440', '#b08d57']],
            'Familiale' => ['bi-people', 'Pensées pour les familles : espaces modulables et confort pour tous.', ['#35523e', '#a7c4a0']],
        ];
        $categories = [];
        $palette = [];
        foreach ($categoriesData as $name => [$icon, $desc, $colors]) {
            $c = (new Category())->setName($name)->setIcon($icon)->setDescription($desc)
                ->setSlug(strtolower($this->slugger->slug($name)));
            $manager->persist($c);
            $categories[$name] = $c;
            $palette[$name] = $colors;
        }

        // ---------- Équipements ----------
        $amenitiesData = [
            'Wi-Fi haut débit' => 'bi-wifi', 'Climatisation' => 'bi-snow', 'Smart TV' => 'bi-tv',
            'Vue mer' => 'bi-water', 'Balcon privé' => 'bi-flower1', 'Minibar' => 'bi-cup-straw',
            'Coffre-fort' => 'bi-safe', 'Baignoire' => 'bi-droplet', 'Machine à café' => 'bi-cup-hot',
            'Espace bureau' => 'bi-laptop',
        ];
        $amenities = [];
        foreach ($amenitiesData as $name => $icon) {
            $a = (new Amenity())->setName($name)->setIcon($icon);
            $manager->persist($a);
            $amenities[$name] = $a;
        }

        // ---------- Chambres ----------
        $roomsData = [
            ['Chambre Jasmin', 'Standard', 180, 2, 24, '1 lit Queen size', false, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Coffre-fort']],
            ['Chambre Olivier', 'Standard', 165, 2, 22, '2 lits simples', false, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV']],
            ['Chambre Solo Riad', 'Standard', 120, 1, 16, '1 lit double', false, ['Wi-Fi haut débit', 'Climatisation', 'Espace bureau']],
            ['Supérieure Jardin d\'Orangers', 'Supérieure', 240, 2, 30, '1 lit King size', false, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Balcon privé', 'Minibar', 'Machine à café']],
            ['Supérieure Méditerranée', 'Supérieure', 265, 3, 32, '1 lit King size + 1 lit d\'appoint', true, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Vue mer', 'Minibar', 'Coffre-fort']],
            ['Deluxe Corniche', 'Deluxe', 340, 2, 38, '1 lit King size', true, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Vue mer', 'Balcon privé', 'Minibar', 'Baignoire', 'Machine à café']],
            ['Deluxe Panorama', 'Deluxe', 360, 3, 42, '1 lit King size + canapé-lit', false, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Vue mer', 'Balcon privé', 'Coffre-fort', 'Baignoire', 'Espace bureau']],
            ['Suite Junior Médina', 'Suite', 450, 3, 55, '1 lit King size + salon', false, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Balcon privé', 'Minibar', 'Coffre-fort', 'Baignoire', 'Machine à café']],
            ['Suite Carthage', 'Suite', 620, 4, 75, '1 lit King size + 2 lits simples', true, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Vue mer', 'Balcon privé', 'Minibar', 'Coffre-fort', 'Baignoire', 'Machine à café', 'Espace bureau']],
            ['Suite Présidentielle Washington', 'Suite', 980, 4, 110, '1 lit Emperor + chambre d\'amis', true, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Vue mer', 'Balcon privé', 'Minibar', 'Coffre-fort', 'Baignoire', 'Machine à café', 'Espace bureau']],
            ['Familiale Oasis', 'Familiale', 390, 5, 58, '1 lit Queen size + 3 lits simples', true, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Balcon privé', 'Minibar', 'Coffre-fort']],
            ['Familiale Lagune', 'Familiale', 420, 6, 64, '2 lits Queen size + 2 lits superposés', false, ['Wi-Fi haut débit', 'Climatisation', 'Smart TV', 'Vue mer', 'Coffre-fort', 'Baignoire']],
        ];
        $descriptions = [
            'Standard' => "Pensée pour le repos, cette chambre allie confort contemporain et touches artisanales tunisiennes. Literie premium, salle de douche à l'italienne en zellige et produits d'accueil bio à l'huile d'olive. Le calme absolu à deux pas de la plage.",
            'Supérieure' => "Plus spacieuse, la chambre Supérieure offre un coin salon baigné de lumière, des tissages berbères faits main et une salle de bain en marbre. Idéale pour prolonger les douces soirées méditerranéennes après une journée au spa.",
            'Deluxe' => "Face à la Méditerranée, la Deluxe déploie de larges baies vitrées, un balcon meublé et une baignoire îlot. Chaque détail — du linge en coton égyptien à la machine à expresso — a été choisi pour un séjour d'exception.",
            'Suite' => "Véritable appartement privé, la suite propose un salon indépendant, une terrasse panoramique et un service de majordome sur demande. Un écrin de raffinement où l'architecture andalouse rencontre le design contemporain.",
            'Familiale' => "Conçue pour partager des moments en famille, cette chambre modulable dispose d'un espace nuit séparé pour les enfants, de nombreux rangements et d'un accès direct au jardin et à la piscine familiale.",
        ];
        $rooms = [];
        foreach ($roomsData as $i => [$name, $cat, $price, $cap, $size, $bed, $featured, $ams]) {
            $room = (new Room())
                ->setName($name)
                ->setSlug(strtolower($this->slugger->slug($name)))
                ->setCategory($categories[$cat])
                ->setShortDescription(sprintf('%d m² · %s · jusqu\'à %d personne%s', $size, $bed, $cap, $cap > 1 ? 's' : ''))
                ->setDescription($descriptions[$cat])
                ->setPricePerNight($price)
                ->setCapacity($cap)
                ->setSize($size)
                ->setBedType($bed)
                ->setIsFeatured($featured)
                ->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', 300 - $i * 7)))
                ->setImage($this->generateImage('rooms', 'demo-room-'.($i + 1), $palette[$cat], $i));
            foreach ($ams as $am) {
                $room->addAmenity($amenities[$am]);
            }
            $manager->persist($room);
            $rooms[] = $room;
        }

        // ---------- Services additionnels ----------
        $servicesData = [
            ['Petit-déjeuner buffet', 'Buffet méditerranéen : pâtisseries maison, fruits frais, huile d\'olive, miel et spécialités tunisiennes.', 35, HotelService::PRICING_PER_PERSON_NIGHT, 'bi-cup-hot', ['#7a4b2a', '#e0b67a']],
            ['Spa & Hammam', 'Accès illimité au hammam traditionnel, sauna et piscine intérieure chauffée, avec un gommage au savon noir.', 120, HotelService::PRICING_PER_STAY, 'bi-droplet-half', ['#24474f', '#8cc0c4']],
            ['Navette aéroport', 'Transfert privé aller-retour depuis l\'aéroport Tunis-Carthage ou Enfidha en berline climatisée.', 90, HotelService::PRICING_PER_STAY, 'bi-car-front', ['#2f3542', '#9aa5b1']],
            ['Dîner gastronomique', 'Menu dégustation en 5 services par notre chef, accord mets et vins en option, en terrasse face à la mer.', 160, HotelService::PRICING_PER_STAY, 'bi-egg-fried', ['#4b2335', '#d39aa9']],
            ['Parking privé', 'Place de parking sécurisée et surveillée 24 h/24 au sein de l\'hôtel.', 15, HotelService::PRICING_PER_NIGHT, 'bi-p-square', ['#33405a', '#8fa3c7']],
            ['Late check-out', 'Profitez de votre chambre jusqu\'à 16 h le jour de votre départ.', 60, HotelService::PRICING_PER_STAY, 'bi-clock-history', ['#3d3a28', '#cfc38a']],
        ];
        $services = [];
        foreach ($servicesData as $i => [$name, $desc, $price, $pricing, $icon, $colors]) {
            $s = (new HotelService())->setName($name)->setDescription($desc)->setPrice($price)
                ->setPricingType($pricing)->setIcon($icon)
                ->setImage($this->generateImage('services', 'demo-service-'.($i + 1), $colors, $i + 3));
            $manager->persist($s);
            $services[] = $s;
        }

        // ---------- Utilisateurs ----------
        $admin = (new User())->setEmail('admin@grandwashington.tn')->setFirstName('Erij')->setLastName('Admin')
            ->setPhone('+216 71 000 000')->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt(new \DateTimeImmutable('-1 year'));
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        $demo = (new User())->setEmail('client@grandwashington.tn')->setFirstName('Yasmine')->setLastName('Ben Salah')
            ->setPhone('+216 22 345 678')->setCreatedAt(new \DateTimeImmutable('-8 months'));
        $demo->setPassword($this->hasher->hashPassword($demo, 'Client123!'));
        $manager->persist($demo);

        $sharedHash = $this->hasher->hashPassword($demo, 'Client123!');
        $clients = [$demo];
        $firstNames = ['Amine', 'Sarra', 'Mehdi', 'Ines', 'Karim', 'Nour', 'Youssef', 'Rania', 'Omar', 'Lina', 'Hamza', 'Salma', 'Walid', 'Meriem', 'Aziz', 'Emna', 'Bilel', 'Hiba', 'Fares', 'Chaima', 'Thomas', 'Camille', 'Lucas', 'Léa'];
        $lastNames = ['Trabelsi', 'Gharbi', 'Hammami', 'Jaziri', 'Mansouri', 'Ayari', 'Bouazizi', 'Chebbi', 'Karray', 'Sassi', 'Dridi', 'Martin', 'Bernard', 'Dubois', 'Masmoudi', 'Zouari'];
        foreach ($firstNames as $i => $fn) {
            $ln = $lastNames[$i % count($lastNames)];
            $u = (new User())
                ->setFirstName($fn)->setLastName($ln)
                ->setEmail(strtolower($this->slugger->slug($fn.'.'.$ln)).'@example.com')
                ->setPhone('+216 '.$faker->numerify('## ### ###'))
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-11 months', '-3 days')))
                ->setIsBlocked(22 === $i);
            $u->setPassword($sharedHash);
            $manager->persist($u);
            $clients[] = $u;
        }

        // ---------- Réservations (sans chevauchement par chambre) ----------
        $today = new \DateTimeImmutable('today');
        $requests = [null, null, null, 'Arrivée tardive vers 23 h.', 'Lit bébé si possible.', 'Anniversaire de mariage 🎉', 'Chambre au calme svp.', null];
        $comments = [
            5 => ['Séjour magique ! Personnel aux petits soins et vue à couper le souffle.', 'Tout était parfait, du check-in au petit-déjeuner. On reviendra !', 'Une suite sublime, le hammam est un vrai bijou.', 'Chambre impeccable, literie incroyable, calme absolu.'],
            4 => ['Très bel hôtel, chambre spacieuse. Petit bémol sur le Wi-Fi le soir.', 'Excellent rapport qualité/prix, le buffet est top.', 'Très bon séjour en famille, les enfants ont adoré la piscine.'],
            3 => ['Bien dans l\'ensemble mais un peu bruyant côté jardin.', 'Correct, mais le service en chambre était lent.'],
        ];
        $reviewed = [];

        foreach ($rooms as $ri => $room) {
            $cursor = $today->modify('-330 days')->modify('+'.mt_rand(0, 12).' days');
            $limit = $today->modify('+75 days');
            while ($cursor < $limit) {
                $nights = mt_rand(2, 6);
                $in = $cursor;
                $out = $in->modify("+$nights days");
                $client = 0 === mt_rand(0, 30) ? $demo : $clients[array_rand($clients)];
                $guests = mt_rand(1, $room->getCapacity());

                if ($out <= $today) {
                    $status = mt_rand(1, 100) <= 88 ? Reservation::STATUS_COMPLETED : Reservation::STATUS_CANCELLED;
                } elseif ($in <= $today) {
                    $status = Reservation::STATUS_CONFIRMED;
                } else {
                    $status = mt_rand(1, 100) <= 60 ? Reservation::STATUS_CONFIRMED : (mt_rand(0, 4) ? Reservation::STATUS_PENDING : Reservation::STATUS_CANCELLED);
                }

                $res = (new Reservation())
                    ->setUser($client)->setRoom($room)->setCheckIn($in)->setCheckOut($out)
                    ->setGuests($guests)->setStatus($status)
                    ->setSpecialRequest($requests[array_rand($requests)])
                    ->setCreatedAt($in->modify('-'.mt_rand(3, 40).' days')->setTime(mt_rand(8, 22), mt_rand(0, 59)));

                $total = $room->getPricePerNight() * $nights;
                foreach ($services as $s) {
                    if (mt_rand(1, 100) <= 28) {
                        $res->addExtra($s);
                        $total += $s->computePrice($nights, $guests);
                    }
                }
                $res->setTotalPrice(round($total, 2));
                $manager->persist($res);

                // Avis sur les séjours terminés
                $key = spl_object_id($client).'-'.$ri;
                if (Reservation::STATUS_COMPLETED === $status && !isset($reviewed[$key]) && mt_rand(1, 100) <= 45) {
                    $reviewed[$key] = true;
                    $rating = [5, 5, 5, 4, 4, 4, 3][mt_rand(0, 6)];
                    $review = (new Review())->setUser($client)->setRoom($room)->setRating($rating)
                        ->setComment($comments[$rating][array_rand($comments[$rating])])
                        ->setIsApproved(mt_rand(1, 100) <= 85)
                        ->setCreatedAt($out->modify('+'.mt_rand(1, 5).' days')->setTime(mt_rand(9, 21), mt_rand(0, 59)));
                    $manager->persist($review);
                }

                // Taux d'occupation plus élevé l'été
                $month = (int) $in->format('n');
                $gap = in_array($month, [6, 7, 8], true) ? mt_rand(0, 3) : mt_rand(2, 14);
                $cursor = $out->modify("+$gap days");
            }
        }

        $manager->flush();
    }

    /**
     * Génère une illustration (dégradé + formes) avec GD pour les données
     * de démonstration. En production, les images sont envoyées via l'admin.
     */
    private function generateImage(string $folder, string $name, array $colors, int $seed): string
    {
        $file = $name.'.jpg';
        $path = $this->uploadDir.'/'.$folder.'/'.$file;
        if (!function_exists('imagecreatetruecolor')) {
            return $file;
        }

        $w = 1200;
        $h = 800;
        $img = imagecreatetruecolor($w, $h);
        [$r1, $g1, $b1] = sscanf($colors[0], '#%02x%02x%02x');
        [$r2, $g2, $b2] = sscanf($colors[1], '#%02x%02x%02x');

        // Dégradé diagonal
        for ($y = 0; $y < $h; ++$y) {
            $t = $y / $h;
            $c = imagecolorallocate($img, (int) ($r1 + ($r2 - $r1) * $t), (int) ($g1 + ($g2 - $g1) * $t), (int) ($b1 + ($b2 - $b1) * $t));
            imageline($img, 0, $y, $w, $y, $c);
        }
        imagealphablending($img, true);

        // Soleil / lune
        $sun = imagecolorallocatealpha($img, 255, 236, 200, 70);
        imagefilledellipse($img, 880 + ($seed % 3) * 60, 220, 220, 220, $sun);
        $sun2 = imagecolorallocatealpha($img, 255, 240, 210, 100);
        imagefilledellipse($img, 880 + ($seed % 3) * 60, 220, 340, 340, $sun2);

        // Arches (architecture méditerranéenne)
        $arch = imagecolorallocatealpha($img, 255, 255, 255, 105);
        for ($k = 0; $k < 4; ++$k) {
            $x = 90 + $k * 280;
            $pts = [$x, $h];
            for ($a = 180; $a <= 360; $a += 10) {
                $pts[] = (int) ($x + 90 + 90 * cos(deg2rad($a)));
                $pts[] = (int) (420 + 90 * sin(deg2rad($a)));
            }
            array_push($pts, $x + 180, $h);
            imagefilledpolygon($img, $pts, $arch);
        }

        // Mer / sol
        $sea = imagecolorallocatealpha($img, 10, 25, 40, 60);
        $points = [0, 640];
        for ($x = 0; $x <= $w; $x += 100) {
            $points[] = $x;
            $points[] = 640 + (int) (12 * sin(($x + $seed * 40) / 120));
        }
        array_push($points, $w, $h, 0, $h);
        imagefilledpolygon($img, $points, $sea);

        imagejpeg($img, $path, 86);
        imagedestroy($img);

        return $file;
    }
}
