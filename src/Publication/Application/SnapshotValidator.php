<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Application;

use stdClass;

/**
 * @phpstan-type Player stdClass&object{id:string,revision:string,slug:string,display_name:string,given_name?:string,family_name?:string,birth_year?:int,country?:string,region?:string,biography?:string,photo_id?:string}
 * @phpstan-type Club stdClass&object{id:string,revision:string,slug:string,name:string,abbreviation?:string,aliases?:list<string>,country?:string,region?:string}
 * @phpstan-type Photo stdClass&object{id:string,revision:string,content_sha256:string,mime_type:string,byte_length:int,width:int,height:int,content_url:string,attribution:string}
 * @phpstan-type Membership stdClass&object{player_id:string,club_id:string}
 * @phpstan-type Tombstone stdClass&object{entity_type:'player'|'club'|'media',entity_id:string,revision:string,removed_at_checkpoint:string}
 * @phpstan-type Snapshot stdClass&object{format:string,schema_version:int,registry_id:string,registry_name:string,authority_generation:null,checkpoint:string,exported_at:string,publication_policy:stdClass&object{version:string,purpose:string,dataset_terms_url:string,media_terms_url:string,distribution_scope:string},players:list<Player>,clubs:list<Club>,memberships:list<Membership>,media:list<Photo>,tombstones:list<Tombstone>}
 */
interface SnapshotValidator
{
    /** @return Snapshot */
    public function decode(string $json): \stdClass;
}
