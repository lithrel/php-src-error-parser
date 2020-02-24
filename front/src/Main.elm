module Main exposing (..)

import Browser

import ErrorCall exposing (ErrorCall, Occurence)
import Search exposing (filterBy)
import Http as Http
import Html exposing (Html)
import Json.Decode as Decode exposing (Decoder, list, string, int)
import Element exposing
    ( Element, layout
    , column, row, text, el, paragraph, link, none
    , width, fill, alignRight, paddingXY, spacing
    , centerX, centerY, alignBottom, alignLeft
    , width, height, px, rgb255
    )
import Element.Events as Ev
import Element.Font as Font
import Element.Input as Input
import Element.Border as Border
import Html.Parser
import Html.Parser.Util

-- UPDATE

update : Msg -> Model -> ( Model, Cmd Msg )
update message model =
    case message of
        NotAsked ->
            ( {model | errors = [] }, Cmd.none )

        Loading ->
            ( {model | errors = [] }, getErrors )

        Failure ->
            ( {model | errors = [] }, Cmd.none )

        ErrorListReceived result ->
            case result of
                Ok errorList ->
                    ( {model | errors = errorList }, Cmd.none )
                Err e ->
                    ( {model | errors = [] }, Cmd.none )

        SearchChanged search ->
            ( {model | search = search, selectedError = Nothing }, Cmd.none )

        ErrorSelected error ->
            if model.selectedError == Just error then
                ( { model | selectedError = Nothing } , Cmd.none )
            else
                ( { model | selectedError = Just error } , Cmd.none )


-- VIEW

view : Model -> Html Msg
view model =
    layout []
        <| column [ centerX ]
            [ row [ centerX ] [ viewHeader ]
            , row [ alignLeft ] [ viewSearch model.search ]
            , column [ width (px 800) ]
                ( viewErrorList model.errors model.search model.selectedError )
            ]

viewHeader : Element Msg
viewHeader =
    el [ paddingXY 0 20, Font.size 23 ] ( text "PHP Errors" )

viewSearch : String ->Element Msg
viewSearch search =
        Input.search [ Input.focusedOnLoad ]
            { onChange = SearchChanged
            , text = search
            , placeholder = Nothing
            , label = Input.labelHidden "Search"
            }

viewErrorList : List ErrorCall -> String -> Maybe ErrorCall -> List (Element Msg)
viewErrorList errors search selected =
    let
        errorList =
            if search == "" then
                List.map (\e -> (e, isSelected e selected)) errors
            else
                List.map (\e -> (e, isSelected e selected))
                    <| filterBy errors search
    in
    List.map viewErrorSummary errorList

viewErrorSummary : (ErrorCall, Bool) -> Element Msg
viewErrorSummary (error, selected) =
    let
        errorHug =
            if selected then
                viewErrorHug error.hugger
            else
                none
        hugStyle =
            if selected then
                [ width (px 600)
                , centerX
                , paddingXY 10 20
                , Border.widthEach { bottom = 1, left = 0, right = 0, top = 0 }
                , Border.color (rgb255 220 220 220)
                ]
            else
                []

    in
    column [ width fill, paddingXY 0 10, spacing 5 ]
        [ paragraph
            [ Font.bold, Font.size 18, Ev.onClick (ErrorSelected error)  ]
            [ text error.message ]
        , paragraph
            [ Font.size 12, width fill ]
            ( viewErrorOccurences error.occ )
        , paragraph
            hugStyle
            [ errorHug ]
        ]

viewErrorOccurences : List Occurence -> List (Element Msg)
viewErrorOccurences occurences =
    ( List.map viewErrorOccurence occurences )

viewErrorOccurence : Occurence -> Element msg
viewErrorOccurence occ =
    let
        repoLabel = occ.file ++ "(" ++ String.fromInt occ.line ++ ")"
        repoUrl = "https://github.com/php/php-src/blob/master"
            ++ occ.file ++ "#L" ++ String.fromInt occ.line
        zendDetails =
            if occ.level /= "" then
                occ.level ++ " | " ++ occ.function
            else
                occ.function
    in
    paragraph [ width fill ]
        [ link [] { label = text repoLabel, url = repoUrl }
        , el
            [ alignRight, Font.color (rgb255 160 160 160) ]
            ( text zendDetails )
        ]


viewErrorHug : String -> Element msg
viewErrorHug hugger =
    column [ centerX, spacing 10, Font.size 14, Font.color (rgb255 100 120 150) ]
        ([ el [ Font.size 18 ] ( text "What happened ?") ]
        ++ huggerToElement hugger)
        -- , el [] ( text hugger )
        -- , huggerToElement hugger
        -- ]

huggerToElement : String -> List (Element msg)
huggerToElement hugger =
    [ el [ Font.size 12, Font.family [Font.typeface "monospace"] ] ( text hugger ) ]
    --let
    --    parsed = Html.Parser.run hugger
    --in
    --case parsed of
    --    Ok e -> List.map Element.html <| Html.Parser.Util.toVirtualDom e
    --    Err _ -> [ el [] ( text hugger ) ]


-- Should definitely have an id and pass it around
isSelected : ErrorCall -> Maybe ErrorCall -> Bool
isSelected error selected =
    case selected of
        Just e -> error == e
        Nothing -> False

-- DECODER

errorListDecoder : Decoder (List ErrorCall)
errorListDecoder =
    list ErrorCall.decoder


-- TYPES

type Msg =
    NotAsked
    | Loading
    | Failure
    | ErrorListReceived (Result Http.Error (List ErrorCall))
    | ErrorSelected ErrorCall
    | SearchChanged String

type alias Model =
    { errors : List ErrorCall
    , search : String
    , selectedError: Maybe ErrorCall
    , page : Page
    }

type Page =
    Search
    | ErrorDetail

-- MAIN | INIT

main =
    Browser.element
        { init = init
        , update = update
        , view = view
        , subscriptions = \_ -> Sub.none
        }

init : () -> ( Model, Cmd Msg )
init _ =
    (
        { errors = []
        , search = ""
        , selectedError = Nothing
        , page = Search
        }
    , getErrors )

getErrors : Cmd Msg
getErrors =
  Http.get
    { url = "api/errors.json"
    , expect = Http.expectJson ErrorListReceived errorListDecoder
    }