# NinjaForms Submissions REST endpoint

A WordPress plugin to make NinjaForms submissions available to a remote system.
This plugin creates a custom endpoint for WordPress REST API protected by an API key.

It is a ready to use WordPress plugin but in my opinion just for developers so I don't put it on the wordpress.org plugin repository (at least not yet).

There are two endpoints:

- `/wp-json/nf-submissions/v1/form/FORM_ID/` to get submissions
- `/wp-json/nf-submissions/v1/form/FORM_ID/fields` to get field labels and metadata

## Installation

1. Download the current version as ZIP file
2. Go to your WordPress backend and navigate to `Plugins` -> `add new`
3. click `Upload plugin`
4. upload the plugin your downloaded in step 1
5. activate the plugin

## Configuration

Go to `NinjaForms`-> `Settings`
There's a new meta box called `Submissions REST API` containing your API key.

## Usage

#### Required parameters:

- FORM_ID ... the numeric ID of your Ninja form

#### Optional Parameters:

- DATE_FROM ... submission start date (only for submissions endpoint)
- DATE_TO ... submission end date (only for submissions endpoint)
- USE_ADMIN_LABEL ... when you retrieve the field labels you can decide whether to use default labels (0) or admin label (1) (only for fields endpoint)

#### Required Header:

- NF-REST-Key ... the key you get from NinjaForms settings of the remote site

### Get field names and metadata

#### CURL

```
curl -X GET \
  'https://YOUR-REMOTE-SITE.com/wp-json/nf-submissions/v1/form/FORM_ID/fields?use_admin_label=0' \
  -H 'NF-REST-Key: XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
```

#### PHP

```
$request = new HttpRequest();
$request->setUrl('https://YOUR-REMOTE-SITE.com/wp-json/nf-submissions/v1/form/FORM_ID/fields');
$request->setMethod(HTTP_METH_GET);

$request->setQueryData(array(
  'use_admin_label' => '0'
));

$request->setHeaders(array(
  'NF-REST-Key' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
));

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

From another WordPress site:

```
$url = 'https://YOUR-REMOTE-SITE.COM/wp-json/nf-submissions/v1/form/' . $form_id . '/fields';
$url = add_query_arg( array(
    'date_from' => $submission_date_from,
    'date_to' => $submission_date_to
), $url );
$response = wp_remote_get(
        $url,
        array(
            'headers' => array(
                "Content-Type"  => "application/json",
                "NF-REST-Key"   => $api_key
            )
        )
    );

$remote_data = json_decode( wp_remote_retrieve_body( $response ), true );
```

### Fields Result:

```
{
    "submission_date": {
        "key": "submission_date",
        "label": "Submission Date"
    },
    "_seq_num": {
        "key": "_seq_num",
        "label": "Submission ID"
    },
    "checkbox_1513101298442": {
        "id": 1034,
        "type": "checkbox",
        "key": "checkbox_1513101298442",
        "label": "Single Checkbox"
    },
    "checkbox_list-greater_than_now_numeric_1556114758245": {
        "id": 1047,
        "type": "listcheckbox",
        "key": "checkbox_list-greater_than_now_numeric_1556114758245",
        "label": "Checkbox List->now numeric",
        "options": {
            "1": "One",
            "2": "Two",
            "3": "Three"
        }
    },
    "listmultiselect_1513101300788": {
        "id": 1035,
        "type": "listmultiselect",
        "key": "listmultiselect_1513101300788",
        "label": "Multi-Select",
        "options": {
            "one": "One",
            "two": "Two",
            "three": "Three"
        }
    },

...
```

### Get submissions

#### CURL

```
curl -X GET \
  'https://YOUR-REMOTE-SITE.com/wp-json/nf-submissions/v1/form/FORM_ID/' \
  -H 'NF-REST-Key: XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
```

or

```
curl -X GET \
  'https://YOUR-REMOTE-SITE.com/wp-json/nf-submissions/v1/form/FORM_ID/?date_from=2019-05-01' \
  -H 'NF-REST-Key: XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
```

### Submission Result:

```
{
    "submissions": [
        {
            "_field_1034": "1",
            "_field_1047": "a:1:{i:0;s:1:\"1\";}",
            "_field_1035": "a:1:{i:0;s:3:\"two\";}",
            "_field_1036": "three",
            "_field_1037": "one",
            "_field_1038": "",
            "_field_1039": "",
            "_field_1040": "US",
            "_field_1041": "",
            "_field_1042": "",
            "_field_1043": "",
            "_field_1045": "5",
            "_seq_num": "8",
            "submission_date": "2019-04-24 16:08:00"
        },
        {
            "_field_1034": "0",
            "_field_1047": "a:2:{i:0;s:3:\"one\";i:1;s:3:\"two\";}",
            "_field_1035": "a:1:{i:0;s:3:\"two\";}",
            "_field_1036": "two",
            "_field_1037": "one",
            "_field_1038": "",
            "_field_1039": "",
            "_field_1040": "US",
            "_field_1041": "",
            "_field_1042": "",
            "_field_1043": "",
            "_field_1045": "5",
            "_seq_num": "7",
            "submission_date": "2018-10-16 16:32:46"
        },
```

## more NinjaForms plugins

We've got a few more WordPress plugins, also for NinjaForms [on our website](https://codemiq.com/en/wordpress-plugins/)

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
