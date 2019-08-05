  array (
    "tableName" => "molecule",
    "fieldName" => "molecule_auto",
    "priority" => -103,
    "type" => "text",
  ),
array (
    "tableName" => "molecule_names",
    "fieldName" => "molecule_name",
    "priority" => -102,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "cas_nr",
    "priority" => -101,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "migrate_id_cheminstor",
    "priority" => -100,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molfile_blob",
    "priority" => -99,
    "type" => "structure",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_barcode",
    "priority" => -99,
    "type" => "text",
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "emp_formula",
    "priority" => -98,
    "type" => "emp_formula",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "mw",
    "priority" => -97,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_type",
    "fieldName" => "molecule_type_name",
    "priority" => -96,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "storage",
    "fieldName" => "storage_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage_chemical_storage_type",
    "fieldName" => "chemical_storage_chemical_storage_type_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage_type",
    "fieldName" => "chemical_storage_type_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_names",
    "fieldName" => "molecule_names_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_molecule_type",
    "fieldName" => "molecule_molecule_type_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_shared",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_property",
    "fieldName" => "molecule_property_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_type",
    "fieldName" => "molecule_type_secret",
    "priority" => -90,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "storage_count",
    "priority" => -80,
    "type" => "num",
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "supplier_offer_count",
    "priority" => -79,
    "type" => "num",
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "compartment",
    "priority" => -70,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "storage",
    "fieldName" => "storage_name",
    "priority" => -70,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "from_reaction_chemical_id",
    "priority" => 1,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "purity",
    "priority" => 2,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "description",
    "priority" => 3,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "order_date",
    "priority" => 4,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "open_date",
    "priority" => 5,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "amount",
    "priority" => 6,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "m",
      ,       "v",
    )  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "actual_amount",
    "priority" => 7,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "m",
      ,       "v",
    )  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "tmd",
    "priority" => 8,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "m",
    )  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_conc",
    "priority" => 9,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "c",
      ,       "m/m",
      ,       "molal",
    )  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_solvent",
    "priority" => 10,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "expiry_date",
    "priority" => 11,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "container",
    "priority" => 12,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "cat_no",
    "priority" => 13,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "lot_no",
    "priority" => 14,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "protection_gas",
    "priority" => 15,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "disposed_when",
    "priority" => 16,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "disposed_by",
    "priority" => 17,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "comment_cheminstor",
    "priority" => 19,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_btm_list",
    "priority" => 21,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_sprengg_list",
    "priority" => 22,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "safety_sheet_by",
    "priority" => 23,
    "type" => "text",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "alt_safety_sheet_by",
    "priority" => 24,
    "type" => "text",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "inventory_check_by",
    "priority" => 25,
    "type" => "text",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "inventory_check_when",
    "priority" => 26,
    "type" => "date",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "supplier",
    "priority" => 27,
    "type" => "text",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "price",
    "priority" => 28,
    "type" => "money",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_int0",
    "priority" => 29,
    "type" => "num",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_int1",
    "priority" => 30,
    "type" => "num",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_dbl0",
    "priority" => 31,
    "type" => "num",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_dbl1",
    "priority" => 32,
    "type" => "num",
    "allowedClasses" => NULL
  ),

array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_disabled",
    "priority" => 34,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_created_by",
    "priority" => 35,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_created_when",
    "priority" => 36,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_changed_by",
    "priority" => 37,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage",
    "fieldName" => "chemical_storage_changed_when",
    "priority" => 38,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "storage",
    "fieldName" => "storage_created_by",
    "priority" => 41,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "storage",
    "fieldName" => "storage_created_when",
    "priority" => 42,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "storage",
    "fieldName" => "storage_changed_by",
    "priority" => 43,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "storage",
    "fieldName" => "storage_changed_when",
    "priority" => 44,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage_type",
    "fieldName" => "chemical_storage_type_name",
    "priority" => 46,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage_type",
    "fieldName" => "chemical_storage_type_text",
    "priority" => 47,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage_type",
    "fieldName" => "chemical_storage_type_created_by",
    "priority" => 49,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage_type",
    "fieldName" => "chemical_storage_type_created_when",
    "priority" => 50,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage_type",
    "fieldName" => "chemical_storage_type_changed_by",
    "priority" => 51,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "chemical_storage_type",
    "fieldName" => "chemical_storage_type_changed_when",
    "priority" => 52,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "mw_monoiso",
    "priority" => 57,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "rdb",
    "priority" => 58,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "density_20",
    "priority" => 59,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "n_20",
    "priority" => 60,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "mp_high",
    "priority" => 61,
    "type" => "range",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "bp_high",
    "priority" => 62,
    "type" => "range",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_r",
    "priority" => 63,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_h",
    "priority" => 64,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_s",
    "priority" => 65,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_p",
    "priority" => 66,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_sym",
    "priority" => 67,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_sym_ghs",
    "priority" => 68,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "comment_mol",
    "priority" => 69,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "migrate_id_mol",
    "priority" => 70,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_cancer",
    "priority" => 71,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_mutagen",
    "priority" => 72,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_reprod",
    "priority" => 73,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_wgk",
    "priority" => 74,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "safety_danger",
    "priority" => 75,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_btm_list",
    "priority" => 76,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_sprengg_list",
    "priority" => 77,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "default_safety_sheet_by",
    "priority" => 78,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "alt_default_safety_sheet_by",
    "priority" => 79,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "pos_liste",
    "priority" => 80,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "neg_liste",
    "priority" => 81,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_int0",
    "priority" => 82,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_int1",
    "priority" => 83,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_dbl0",
    "priority" => 84,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_dbl1",
    "priority" => 85,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_created_by",
    "priority" => 87,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_created_when",
    "priority" => 88,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_changed_by",
    "priority" => 89,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "molecule_changed_when",
    "priority" => 90,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule",
    "fieldName" => "reaction_count",
    "priority" => 94,
    "type" => "num",
  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "FP",
    "priority" => 96,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "T",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "mp",
    "priority" => 97,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "T",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "bp",
    "priority" => 98,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "T",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "Autoign_temp",
    "priority" => 99,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "T",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "extinguishant",
    "priority" => 100,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "VbF",
    "priority" => 101,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "Sol_text",
    "priority" => 102,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "UN_No",
    "priority" => 103,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "EG_No",
    "priority" => 104,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "EG_Idx_No",
    "priority" => 105,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "adr",
    "priority" => 106,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "imdg",
    "priority" => 107,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "iata",
    "priority" => 108,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "packing_group",
    "priority" => 109,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "antidot",
    "priority" => 110,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "substitutes",
    "priority" => 111,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "risk_assessment",
    "priority" => 112,
    "type" => "text",
    "allowedClasses" => array (
      ,       "Text",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "Sol_water",
    "priority" => 113,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "density",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "Sat_conc_air",
    "priority" => 114,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "density",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "MAK",
    "priority" => 115,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "density",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "MAK_vol",
    "priority" => 116,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "v/v",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "TRK",
    "priority" => 117,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "density",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "TRK_vol",
    "priority" => 118,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "v/v",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "Ex_limits",
    "priority" => 119,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "v/v",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "Vap_press",
    "priority" => 120,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "p",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "Kin_visc",
    "priority" => 121,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "kin_visc",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "LD50_or",
    "priority" => 122,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "m/m",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "LD50_derm",
    "priority" => 123,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "m/m",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "IC50",
    "priority" => 124,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "c",
    )  ),
array (
    "tableName" => "molecule",
    "fieldNamePrefix" => "molecule_property_flat/",
    "fieldName" => "rotation_20",
    "priority" => 125,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "ang",
    )  ),
array (
    "tableName" => "molecule_type",
    "fieldName" => "molecule_type_text",
    "priority" => 130,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_type",
    "fieldName" => "molecule_type_created_by",
    "priority" => 132,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_type",
    "fieldName" => "molecule_type_created_when",
    "priority" => 133,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_type",
    "fieldName" => "molecule_type_changed_by",
    "priority" => 134,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_type",
    "fieldName" => "molecule_type_changed_when",
    "priority" => 135,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_property",
    "fieldName" => "molecule_property_created_by",
    "priority" => 137,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_property",
    "fieldName" => "molecule_property_created_when",
    "priority" => 138,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_property",
    "fieldName" => "molecule_property_changed_by",
    "priority" => 139,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "molecule_property",
    "fieldName" => "molecule_property_changed_when",
    "priority" => 140,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "project_id",
    "priority" => 141,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_type_id",
    "priority" => 142,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "lab_journal_id",
    "priority" => 143,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "realization_text_fulltext",
    "priority" => 144,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "realization_observation_fulltext",
    "priority" => 145,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "rxn_smiles",
    "priority" => 146,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "rxnfile_blob",
    "priority" => 147,
    "type" => "r_structure",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_carried_out_by",
    "priority" => 148,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_title",
    "priority" => 149,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_quality",
    "priority" => 150,
    "type" => "num",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_started_when",
    "priority" => 151,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "ref_amount",
    "priority" => 152,
    "type" => "num_unit",
    "allowedClasses" => array (
      ,       "n",
    )  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_disabled",
    "priority" => 154,
    "type" => "bool",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_created_by",
    "priority" => 155,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_created_when",
    "priority" => 156,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_changed_by",
    "priority" => 157,
    "type" => "text",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "reaction",
    "fieldName" => "reaction_changed_when",
    "priority" => 158,
    "type" => "date",
    "allowedClasses" => NULL
  ),
array (
    "tableName" => "storage",
    "fieldName" => "storage_barcode",
    "priority" => 160,
    "type" => "text",
  )